<?php

namespace App\Services;

use App\Models\PaymentApiLog;
use App\Models\PaymentOrder;
use Illuminate\Support\Facades\Log;

class PaymentApiLogger
{
    /**
     * @param  array<string, mixed>|null  $requestBody
     */
    public function record(
        string $direction,
        ?PaymentOrder $order,
        string $url,
        ?array $requestBody = null,
        ?int $responseStatus = null,
        mixed $responseBody = null,
        ?string $error = null,
    ): ?PaymentApiLog {
        try {
            return PaymentApiLog::query()->create([
                'payment_order_id' => $order?->id,
                'merchant_id' => $order?->merchant_id,
                'direction' => $direction,
                'url' => $url,
                'request_body' => $this->redactRequest($direction, $requestBody),
                'response_status' => $responseStatus,
                'response_body' => $this->normalizeBody($responseBody),
                'error' => $error,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Payment API log failed.', [
                'direction' => $direction,
                'order_id' => $order?->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $requestBody
     * @return array<string, mixed>|null
     */
    private function redactRequest(string $direction, ?array $requestBody): ?array
    {
        if ($requestBody === null) {
            return null;
        }

        if ($direction === PaymentApiLog::DIRECTION_UPSTREAM) {
            unset($requestBody['key']);
        }

        return $requestBody;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeBody(mixed $body): ?array
    {
        if ($body === null || $body === '') {
            return null;
        }

        if (is_array($body)) {
            return $body;
        }

        return ['raw' => (string) $body];
    }
}
