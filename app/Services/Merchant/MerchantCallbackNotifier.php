<?php

namespace App\Services\Merchant;

use App\Models\Merchant;
use App\Models\PaymentApiLog;
use App\Models\PaymentOrder;
use App\Services\PaymentApiLogger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MerchantCallbackNotifier
{
    public function __construct(
        private readonly MerchantSignService $signer,
        private readonly PaymentApiLogger $apiLogs,
    ) {}

    public function notify(PaymentOrder $order): void
    {
        $order->loadMissing('merchant');
        $merchant = $order->merchant;
        $url = $order->callback_url;

        if (! $merchant instanceof Merchant || $url === '') {
            return;
        }

        $payload = [
            'type' => $order->type,
            'merchantOrderNo' => $order->merchant_order_no,
            'ref' => (string) $order->upstream_ref,
            'status' => $order->status,
            'amount' => $this->plainAmount($order->amount),
            'completedAmount' => $this->plainAmount($order->completed_amount),
            'remainingAmount' => $this->plainAmount($order->remaining_amount),
        ];
        $payload['sign'] = $this->signer->sign($payload, (string) $merchant->md5key);

        try {
            $request = Http::acceptJson()->asJson()->timeout(8);
            $proxy = config('services.http_client.proxy');
            if (filled($proxy)) {
                $request = $request->withOptions(['proxy' => $proxy]);
            }

            $response = $request->post($url, $payload);
            $body = $response->json();
            $this->apiLogs->record(
                PaymentApiLog::DIRECTION_MERCHANT_CALLBACK,
                $order,
                $url,
                $payload,
                $response->status(),
                is_array($body) ? $body : $response->body(),
            );
            $order->forceFill([
                'merchant_notified_at' => now(),
                'merchant_notify_status' => (string) $response->status(),
            ])->save();
        } catch (\Throwable $exception) {
            $this->apiLogs->record(
                PaymentApiLog::DIRECTION_MERCHANT_CALLBACK,
                $order,
                $url,
                $payload,
                null,
                null,
                $exception->getMessage(),
            );
            Log::warning('Merchant callback failed.', [
                'order_id' => $order->id,
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);
            $order->forceFill([
                'merchant_notify_status' => 'error',
            ])->save();
        }
    }

    private function plainAmount(mixed $amount): string
    {
        $value = number_format((float) $amount, 4, '.', '');

        return rtrim(rtrim($value, '0'), '.') ?: '0';
    }
}
