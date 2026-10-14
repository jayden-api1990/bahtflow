<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantCredit;
use App\Models\PaymentApiLog;
use App\Models\PaymentOrder;
use App\Services\Merchant\MerchantCallbackNotifier;
use App\Services\PaymentGateway\JinlipayClient;
use App\Services\PaymentGateway\JinlipayException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MerchantPaymentService
{
    public function __construct(
        private readonly JinlipayClient $client,
        private readonly MerchantCreditAdjustmentService $credit,
        private readonly MerchantCallbackNotifier $notifier,
        private readonly PaymentApiLogger $apiLogs,
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

        $fee = $this->percentFee($amount, $merchant->deposit_fee);
        $merchantOrderNo = (string) $input['merchantOrderNo'];
        $this->assertOrderAvailable($merchant, $merchantOrderNo);

        $upstream = $this->client->createDeposit([
            'amount' => $amount,
            'fromAccount' => $input['fromAccount'],
            'fromBankCode' => $input['fromBankCode'],
            'fullName' => $input['fullName'],
            'fullNameEn' => $input['fullNameEn'] ?? null,
            'returnUrl' => $input['returnUrl'] ?? null,
            'detail' => $merchantOrderNo,
        ]);
        $this->assertUpstreamAccepted($upstream);

        $data = is_array($upstream['data'] ?? null) ? $upstream['data'] : [];
        $order = PaymentOrder::query()->create([
            'merchant_id' => $merchant->id,
            'type' => PaymentOrder::TYPE_DEPOSIT,
            'merchant_order_no' => $merchantOrderNo,
            'amount' => $amount,
            'amount_fee' => $fee,
            'status' => PaymentOrder::STATUS_PENDING,
            'callback_url' => $callbackUrl,
            'return_url' => $input['returnUrl'] ?? null,
            'request_payload' => $input,
            'upstream_response' => $upstream,
            'upstream_ref' => $data['ref'] ?? null,
            'upload_url' => $data['uploadUrl'] ?? null,
            'upload_token' => $data['uploadToken'] ?? null,
        ]);
        $this->apiLogs->record(PaymentApiLog::DIRECTION_MERCHANT_REQUEST, $order, PaymentOrder::TYPE_DEPOSIT, $input);
        $this->client->attachLastUpstreamLog($order);

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

        $this->client->cancelDeposit($order->upstream_ref, $order);
        $order->forceFill([
            'status' => PaymentOrder::STATUS_CANCELLED,
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
        $fee = $this->percentFee($amount, $merchant->withdraw_fee);
        $lock = bcadd($this->money($amount), $fee, 4);
        $merchantOrderNo = (string) $input['merchantOrderNo'];
        $this->assertOrderAvailable($merchant, $merchantOrderNo);
        $this->assertSufficientBalance($merchant, $lock);

        $upstream = $this->client->createWithdraw([
            'amount' => $amount,
            'toAccount' => $input['toAccount'],
            'toBankCode' => $input['toBankCode'],
            'toAccountName' => $input['toAccountName'],
            'toAccountNameEn' => $input['toAccountNameEn'] ?? null,
            'detail' => $merchantOrderNo,
        ]);
        $this->assertUpstreamAccepted($upstream);

        $data = is_array($upstream['data'] ?? null) ? $upstream['data'] : [];
        $order = DB::transaction(function () use ($merchant, $input, $upstream, $amount, $fee, $lock, $callbackUrl, $merchantOrderNo, $data): PaymentOrder {
            $credit = MerchantCredit::query()->where('merchant_id', $merchant->id)->lockForUpdate()->first();
            if (! $credit || bccomp($this->money($credit->credit_balance), $lock, 4) === -1) {
                throw new RuntimeException('INSUFFICIENT_BALANCE');
            }

            $order = PaymentOrder::query()->create([
                'merchant_id' => $merchant->id,
                'type' => PaymentOrder::TYPE_WITHDRAW,
                'merchant_order_no' => $merchantOrderNo,
                'amount' => $amount,
                'amount_fee' => $fee,
                'status' => PaymentOrder::STATUS_PENDING,
                'callback_url' => $callbackUrl,
                'request_payload' => $input,
                'upstream_response' => $upstream,
                'credit_locked' => $lock,
                'remaining_amount' => $amount,
                'upstream_ref' => $data['ref'] ?? null,
            ]);
            $this->apiLogs->record(PaymentApiLog::DIRECTION_MERCHANT_REQUEST, $order, PaymentOrder::TYPE_WITHDRAW, $input);
            $this->credit->adjust($credit->id, 'decrease', (float) $lock, 'withdraw lock '.$order->merchant_order_no);

            return $order;
        });
        $this->client->attachLastUpstreamLog($order);

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
            $this->apiLogs->record(PaymentApiLog::DIRECTION_UPSTREAM_CALLBACK, $order, PaymentOrder::TYPE_DEPOSIT, $payload, null, null, 'ORDER_NOT_FOUND');
            throw new RuntimeException('ORDER_NOT_FOUND');
        }

        $this->apiLogs->record(PaymentApiLog::DIRECTION_UPSTREAM_CALLBACK, $order, PaymentOrder::TYPE_DEPOSIT, $payload);

        if ($order->isFinal() && $this->depositSettlementDone($order)) {
            return;
        }

        DB::transaction(function () use ($order, $payload, $status): void {
            $locked = PaymentOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->isFinal() && $this->depositSettlementDone($locked)) {
                return;
            }

            $needsCredit = (float) $locked->credit_applied <= 0 && (float) $locked->completed_amount <= 0;
            $locked->status = $status;

            if ($status === PaymentOrder::STATUS_COMPLETE && $needsCredit) {
                $gross = $this->money($this->client->canonicalAmount($payload['amount'] ?? $locked->amount));
                $fee = $this->proportionalFee($this->money($locked->amount), $this->money($locked->amount_fee), $gross);
                $net = bcsub($gross, $fee, 4);
                $credit = MerchantCredit::query()->where('merchant_id', $locked->merchant_id)->first();
                if ($credit && bccomp($net, '0', 4) === 1) {
                    $this->credit->adjust($credit->id, 'increase', (float) $net, 'deposit complete '.$locked->upstream_ref);
                }
                $locked->amount_fee = $fee;
                $locked->credit_applied = $net;
                $locked->completed_amount = $gross;
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
            $this->apiLogs->record(PaymentApiLog::DIRECTION_UPSTREAM_CALLBACK, $order, PaymentOrder::TYPE_WITHDRAW, $payload, null, null, 'ORDER_NOT_FOUND');
            throw new RuntimeException('ORDER_NOT_FOUND');
        }

        $this->apiLogs->record(PaymentApiLog::DIRECTION_UPSTREAM_CALLBACK, $order, PaymentOrder::TYPE_WITHDRAW, $payload);

        if ($order->isFinal()) {
            return;
        }

        if ($order->callback_ts !== null && $ts > 0 && $ts < (int) $order->callback_ts) {
            return;
        }

        $applied = DB::transaction(function () use ($order, $payload, $status, $ts): bool {
            $locked = PaymentOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->isFinal()) {
                return false;
            }

            if ($locked->callback_ts !== null && $ts > 0 && $ts < (int) $locked->callback_ts) {
                return false;
            }

            $completed = (float) ($payload['completedAmount'] ?? $locked->completed_amount);
            $remaining = (float) ($payload['remainingAmount'] ?? $locked->remaining_amount);

            $locked->completed_amount = $completed;
            $locked->remaining_amount = $remaining;
            $locked->status = $status;
            if ($ts > 0) {
                $locked->callback_ts = $ts;
            }

            $finals = PaymentOrder::WITHDRAW_FINAL;
            if (in_array($status, $finals, true)) {
                $completedMoney = $this->money($completed);
                $settledFee = $this->proportionalFee($this->money($locked->amount), $this->money($locked->amount_fee), $completedMoney);
                $shouldRemainLocked = bcadd($completedMoney, $settledFee, 4);
                $alreadyKept = bcsub($this->money($locked->credit_locked), $this->money($locked->credit_released), 4);
                $release = bcsub($alreadyKept, $shouldRemainLocked, 4);
                if (bccomp($release, '0', 4) === 1) {
                    $this->releaseWithdraw($locked, (float) $release, 'withdraw settle '.$locked->upstream_ref);
                    $locked->credit_released = bcadd($this->money($locked->credit_released), $release, 4);
                }
                $locked->amount_fee = $settledFee;
            }

            $locked->save();

            return true;
        });

        if ($applied) {
            $fresh = $order->fresh();
            if ($fresh) {
                $this->notifier->notify($fresh);
            }
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

    private function assertOrderAvailable(Merchant $merchant, string $merchantOrderNo): void
    {
        $exists = PaymentOrder::query()
            ->where('merchant_id', $merchant->id)
            ->where('merchant_order_no', $merchantOrderNo)
            ->exists();

        if ($exists) {
            throw new RuntimeException('ORDER_EXISTS');
        }
    }

    private function assertSufficientBalance(Merchant $merchant, string $lock): void
    {
        $credit = MerchantCredit::query()->where('merchant_id', $merchant->id)->first();
        if (! $credit || bccomp($this->money($credit->credit_balance), $lock, 4) === -1) {
            throw new RuntimeException('INSUFFICIENT_BALANCE');
        }
    }

    /**
     * @param  array<string, mixed>  $upstream
     */
    private function assertUpstreamAccepted(array $upstream): void
    {
        $code = (string) ($upstream['code'] ?? '');
        $message = (string) ($upstream['message'] ?? '');
        if ($code === '0000') {
            return;
        }

        $data = is_array($upstream['data'] ?? null) ? $upstream['data'] : [];
        throw new JinlipayException($code !== '' ? $code : '0001', $message !== '' ? $message : 'Upstream request failed.', $data);
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

    private function depositSettlementDone(PaymentOrder $order): bool
    {
        if ($order->status !== PaymentOrder::STATUS_COMPLETE) {
            return true;
        }

        return (float) $order->credit_applied > 0 || (float) $order->completed_amount > 0;
    }

    private function percentFee(mixed $amount, mixed $percent): string
    {
        $base = $this->money($amount);
        $rate = $this->money($percent);
        if (bccomp($base, '0', 4) !== 1 || bccomp($rate, '0', 4) !== 1) {
            return '0.0000';
        }

        $fee = bcadd(bcdiv(bcmul($base, $rate, 8), '100', 8), '0.00005', 4);

        return bccomp($fee, $base, 4) === 1 ? $base : $fee;
    }

    private function proportionalFee(string $orderAmount, string $orderFee, string $portion): string
    {
        if (bccomp($orderAmount, '0', 4) !== 1 || bccomp($orderFee, '0', 4) !== 1 || bccomp($portion, '0', 4) !== 1) {
            return '0.0000';
        }

        if (bccomp($portion, $orderAmount, 4) >= 0) {
            return bccomp($orderFee, $portion, 4) === 1 ? $portion : $orderFee;
        }

        $fee = bcadd(bcdiv(bcmul($orderFee, $portion, 8), $orderAmount, 8), '0.00005', 4);

        return bccomp($fee, $portion, 4) === 1 ? $portion : $fee;
    }

    private function money(mixed $value): string
    {
        if (! is_numeric($value)) {
            return '0.0000';
        }

        return bcadd((string) $value, '0', 4);
    }
}
