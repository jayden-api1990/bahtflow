<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantCredit;
use App\Models\PaymentOrder;
use App\Services\PaymentGateway\JinlipayClient;
use App\Services\PaymentGateway\JinlipayException;
use App\Services\Merchant\MerchantCallbackNotifier;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MerchantPaymentService
{
    public function __construct(
        private readonly JinlipayClient $client,
        private readonly MerchantCreditAdjustmentService $credit,
        private readonly MerchantCallbackNotifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function createDeposit(Merchant $merchant, array $input): array
    {
        $this->assertMerchantActive($merchant);
        $callbackUrl = $this->resolveCallbackUrl($merchant, $input['callbackUrl'] ?? null);
        $amount = $this->client->canonicalAmount($input['amount']);

        $order = PaymentOrder::query()->create([
            'merchant_id' => $merchant->id,
            'type' => PaymentOrder::TYPE_DEPOSIT,
            'merchant_order_no' => (string) $input['merchantOrderNo'],
            'amount' => $amount,
            'status' => PaymentOrder::STATUS_PROCESSING,
            'callback_url' => $callbackUrl,
            'return_url' => $input['returnUrl'] ?? null,
            'request_payload' => $this->requestSnapshot($input),
        ]);

        try {
            $upstream = $this->client->createDeposit([
                'amount' => $amount,
                'fromAccount' => $input['fromAccount'],
                'fromBankCode' => $input['fromBankCode'],
                'fullName' => $input['fullName'],
                'fullNameEn' => $input['fullNameEn'] ?? null,
                'returnUrl' => $input['returnUrl'] ?? null,
                'detail' => $order->merchant_order_no,
            ]);
        } catch (JinlipayException $exception) {
            $order->forceFill([
                'status' => PaymentOrder::STATUS_FAILED,
                'upstream_response' => ['code' => $exception->upstreamCode, 'message' => $exception->getMessage(), 'data' => $exception->data],
            ])->save();
            throw $exception;
        }

        $data = is_array($upstream['data'] ?? null) ? $upstream['data'] : [];
        $order->forceFill([
            'status' => PaymentOrder::STATUS_PENDING,
            'upstream_ref' => $data['ref'] ?? null,
            'upload_url' => $data['uploadUrl'] ?? null,
            'upload_token' => $data['uploadToken'] ?? null,
            'upstream_response' => $upstream,
        ])->save();

        return [
            'merchantOrderNo' => $order->merchant_order_no,
            'ref' => $order->upstream_ref,
            'amount' => $amount,
            'status' => $order->status,
            'expiredDate' => $data['expiredDate'] ?? null,
            'uploadUrl' => $order->upload_url,
            'uploadToken' => $order->upload_token,
            'matchedWithdraws' => $data['matchedWithdraws'] ?? [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelDeposit(Merchant $merchant, string $merchantOrderNo): array
    {
        $this->assertMerchantActive($merchant);

        $order = PaymentOrder::query()
            ->where('merchant_id', $merchant->id)
            ->where('type', PaymentOrder::TYPE_DEPOSIT)
            ->where('merchant_order_no', $merchantOrderNo)
            ->first();

        if (! $order || ! $order->upstream_ref) {
            throw new RuntimeException('ORDER_NOT_FOUND');
        }

        if ($order->isFinal()) {
            throw new RuntimeException('ORDER_FINAL');
        }

        $upstream = $this->client->cancelDeposit($order->upstream_ref);
        $order->forceFill([
            'status' => PaymentOrder::STATUS_CANCELLED,
            'upstream_response' => $upstream,
        ])->save();

        $this->notifier->notify($order->fresh());

        return [
            'merchantOrderNo' => $order->merchant_order_no,
            'ref' => $order->upstream_ref,
            'status' => PaymentOrder::STATUS_CANCELLED,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function createWithdraw(Merchant $merchant, array $input): array
    {
        $this->assertMerchantActive($merchant);
        $callbackUrl = $this->resolveCallbackUrl($merchant, $input['callbackUrl'] ?? null);
        $amount = $this->client->canonicalAmount($input['amount']);

        $order = DB::transaction(function () use ($merchant, $input, $amount, $callbackUrl): PaymentOrder {
            $credit = MerchantCredit::query()->where('merchant_id', $merchant->id)->lockForUpdate()->first();
            if (! $credit || (float) $credit->credit_balance < (float) $amount) {
                throw new RuntimeException('INSUFFICIENT_BALANCE');
            }

            $order = PaymentOrder::query()->create([
                'merchant_id' => $merchant->id,
                'type' => PaymentOrder::TYPE_WITHDRAW,
                'merchant_order_no' => (string) $input['merchantOrderNo'],
                'amount' => $amount,
                'status' => PaymentOrder::STATUS_PROCESSING,
                'callback_url' => $callbackUrl,
                'credit_locked' => $amount,
                'remaining_amount' => $amount,
                'request_payload' => $this->requestSnapshot($input),
            ]);

            $this->credit->adjust($credit->id, 'decrease', (float) $amount, 'withdraw lock '.$order->merchant_order_no);

            return $order;
        });

        try {
            $upstream = $this->client->createWithdraw([
                'amount' => $amount,
                'toAccount' => $input['toAccount'],
                'toBankCode' => $input['toBankCode'],
                'toAccountName' => $input['toAccountName'],
                'toAccountNameEn' => $input['toAccountNameEn'] ?? null,
                'detail' => $order->merchant_order_no,
            ]);
        } catch (JinlipayException $exception) {
            $this->releaseWithdraw($order, (float) $amount, 'withdraw failed '.$order->merchant_order_no);
            $order->forceFill([
                'status' => PaymentOrder::STATUS_FAILED,
                'credit_released' => $amount,
                'remaining_amount' => 0,
                'upstream_response' => ['code' => $exception->upstreamCode, 'message' => $exception->getMessage(), 'data' => $exception->data],
            ])->save();
            $this->notifier->notify($order->fresh());
            throw $exception;
        }

        $data = is_array($upstream['data'] ?? null) ? $upstream['data'] : [];
        $order->forceFill([
            'status' => PaymentOrder::STATUS_PENDING,
            'upstream_ref' => $data['ref'] ?? null,
            'upstream_response' => $upstream,
        ])->save();

        return [
            'merchantOrderNo' => $order->merchant_order_no,
            'ref' => $order->upstream_ref,
            'amount' => $amount,
            'status' => $order->status,
            'expiredDate' => $data['expiredDate'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function balance(Merchant $merchant): array
    {
        $this->assertMerchantActive($merchant);
        $balance = $merchant->credit?->credit_balance ?? '0.0000';

        return [
            'creditBalance' => rtrim(rtrim(number_format((float) $balance, 4, '.', ''), '0'), '.') ?: '0',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleDepositCallback(array $payload): void
    {
        $ref = (string) ($payload['ref'] ?? '');
        $status = (string) ($payload['status'] ?? '');
        $order = PaymentOrder::query()
            ->where('type', PaymentOrder::TYPE_DEPOSIT)
            ->where('upstream_ref', $ref)
            ->first();

        if (! $order || $ref === '' || $status === '') {
            throw new RuntimeException('ORDER_NOT_FOUND');
        }

        if ($order->isFinal() && ! ($order->status === PaymentOrder::STATUS_COMPLETE && (float) $order->credit_applied <= 0)) {
            return;
        }

        DB::transaction(function () use ($order, $payload, $status): void {
            $locked = PaymentOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->isFinal() && ! ($locked->status === PaymentOrder::STATUS_COMPLETE && (float) $locked->credit_applied <= 0)) {
                return;
            }

            $locked->callback_payload = $payload;
            $locked->status = $status;

            if ($status === PaymentOrder::STATUS_COMPLETE && (float) $locked->credit_applied <= 0) {
                $amount = (float) $this->client->canonicalAmount($payload['amount'] ?? $locked->amount);
                $credit = MerchantCredit::query()->where('merchant_id', $locked->merchant_id)->first();
                if ($credit) {
                    $this->credit->adjust($credit->id, 'increase', $amount, 'deposit complete '.$locked->upstream_ref);
                }
                $locked->credit_applied = $amount;
                $locked->completed_amount = $amount;
                $locked->remaining_amount = 0;
            }

            $locked->save();
        });

        $this->notifier->notify($order->fresh());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWithdrawCallback(array $payload): void
    {
        $ref = (string) ($payload['ref'] ?? '');
        $status = (string) ($payload['status'] ?? '');
        $ts = isset($payload['ts']) ? (int) $payload['ts'] : 0;

        $order = PaymentOrder::query()
            ->where('type', PaymentOrder::TYPE_WITHDRAW)
            ->where('upstream_ref', $ref)
            ->first();

        if (! $order || $ref === '' || $status === '') {
            throw new RuntimeException('ORDER_NOT_FOUND');
        }

        if ($order->isFinal()) {
            return;
        }

        if ($order->callback_ts !== null && $ts > 0 && $ts < (int) $order->callback_ts) {
            return;
        }

        DB::transaction(function () use ($order, $payload, $status, $ts): void {
            $locked = PaymentOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->isFinal()) {
                return;
            }

            if ($locked->callback_ts !== null && $ts > 0 && $ts < (int) $locked->callback_ts) {
                return;
            }

            $completed = (float) ($payload['completedAmount'] ?? $locked->completed_amount);
            $remaining = (float) ($payload['remainingAmount'] ?? $locked->remaining_amount);

            $locked->callback_payload = $payload;
            $locked->completed_amount = $completed;
            $locked->remaining_amount = $remaining;
            $locked->status = $status;
            if ($ts > 0) {
                $locked->callback_ts = $ts;
            }

            $finals = PaymentOrder::WITHDRAW_FINAL;
            if (in_array($status, $finals, true)) {
                $shouldRemainLocked = $completed;
                $alreadyKept = (float) $locked->credit_locked - (float) $locked->credit_released;
                $release = round($alreadyKept - $shouldRemainLocked, 4);
                if ($release > 0) {
                    $this->releaseWithdraw($locked, $release, 'withdraw settle '.$locked->upstream_ref);
                    $locked->credit_released = round((float) $locked->credit_released + $release, 4);
                }
            }

            $locked->save();
        });

        $fresh = $order->fresh();
        if ($fresh && $fresh->isFinal()) {
            $this->notifier->notify($fresh);
        }
    }

    private function releaseWithdraw(PaymentOrder $order, float $amount, string $note): void
    {
        if ($amount <= 0) {
            return;
        }

        $credit = MerchantCredit::query()->where('merchant_id', $order->merchant_id)->first();
        if (! $credit) {
            return;
        }

        $this->credit->adjust($credit->id, 'increase', $amount, $note);
    }

    private function assertMerchantActive(Merchant $merchant): void
    {
        if ((int) $merchant->status !== 1) {
            throw new RuntimeException('MERCHANT_DISABLED');
        }
    }

    private function resolveCallbackUrl(Merchant $merchant, mixed $callbackUrl): string
    {
        $url = trim((string) ($callbackUrl ?: $merchant->callback_url));
        if ($url === '') {
            throw new RuntimeException('CALLBACK_URL_REQUIRED');
        }

        return $url;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function requestSnapshot(array $input): array
    {
        unset($input['sign'], $input['merchant_serial']);

        return $input;
    }
}
