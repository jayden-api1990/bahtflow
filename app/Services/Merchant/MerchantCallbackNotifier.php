<?php

namespace App\Services\Merchant;

use App\Models\Merchant;
use App\Models\PaymentOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MerchantCallbackNotifier
{
    public function __construct(private readonly MerchantSignService $signer) {}

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
            $response = Http::acceptJson()->asJson()->timeout(8)->post($url, $payload);
            $order->forceFill([
                'merchant_notified_at' => now(),
                'merchant_notify_status' => (string) $response->status(),
            ])->save();
        } catch (\Throwable $exception) {
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
