<?php

namespace App\Services\PaymentGateway;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class JinlipayClient implements UpstreamClient
{
    public function depositCallbackUrl(): string
    {
        return config('jinlipay.public_url').'/api/jinlipay/deposit-callback';
    }

    public function withdrawCallbackUrl(): string
    {
        return config('jinlipay.public_url').'/api/jinlipay/withdraw-callback';
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public function createDeposit(array $fields): array
    {
        $amount = $this->canonicalAmount($fields['amount']);
        $body = [
            'key' => (string) config('jinlipay.deposit_key'),
            'amount' => $this->amountForJson($amount),
            'fromAccount' => (string) $fields['fromAccount'],
            'fromBankCode' => (string) $fields['fromBankCode'],
            'fullName' => (string) $fields['fullName'],
            'callbackUrl' => $this->depositCallbackUrl(),
        ];

        if (! empty($fields['fullNameEn'])) {
            $body['fullNameEn'] = (string) $fields['fullNameEn'];
        }
        if (! empty($fields['returnUrl'])) {
            $body['returnUrl'] = (string) $fields['returnUrl'];
        }
        if (! empty($fields['detail'])) {
            $body['detail'] = (string) $fields['detail'];
        }

        $body['signature'] = $this->sign([
            $body['key'],
            $amount,
            $body['fromAccount'],
            $body['fromBankCode'],
            $body['callbackUrl'],
        ], (string) config('jinlipay.deposit_secret'));

        return $this->post('/v1/p2p/deposit', $body);
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelDeposit(string $ref): array
    {
        $key = (string) config('jinlipay.deposit_key');
        $body = [
            'key' => $key,
            'ref' => $ref,
            'signature' => $this->sign([$key, $ref], (string) config('jinlipay.deposit_secret')),
        ];

        return $this->post('/v1/p2p/cancel', $body);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    public function createWithdraw(array $fields): array
    {
        $amount = $this->canonicalAmount($fields['amount']);
        $body = [
            'key' => (string) config('jinlipay.withdraw_key'),
            'amount' => $this->amountForJson($amount),
            'toAccount' => (string) $fields['toAccount'],
            'toBankCode' => (string) $fields['toBankCode'],
            'toAccountName' => (string) $fields['toAccountName'],
            'callbackUrl' => $this->withdrawCallbackUrl(),
        ];

        if (! empty($fields['toAccountNameEn'])) {
            $body['toAccountNameEn'] = (string) $fields['toAccountNameEn'];
        }
        if (! empty($fields['detail'])) {
            $body['detail'] = (string) $fields['detail'];
        }

        $body['signature'] = $this->sign([
            $body['key'],
            $amount,
            $body['toAccount'],
            $body['toBankCode'],
            $body['callbackUrl'],
        ], (string) config('jinlipay.withdraw_secret'));

        return $this->post('/v1/p2p/withdraw', $body);
    }

    public function canonicalAmount(int|float|string $amount): string
    {
        if (! is_numeric($amount)) {
            throw new JinlipayException('P2P_011', 'Amount must be numeric.');
        }

        $value = number_format((float) $amount, 4, '.', '');
        $value = rtrim(rtrim($value, '0'), '.');

        if ($value === '' || (float) $value <= 0) {
            throw new JinlipayException('P2P_011', 'Amount must be greater than 0.');
        }

        return $value;
    }

    public function sign(array $values, string $secret): string
    {
        return md5(implode(':', [...array_map('strval', $values), $secret]));
    }

    private function amountForJson(string $canonical): int|float
    {
        return str_contains($canonical, '.') ? (float) $canonical : (int) $canonical;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function post(string $path, array $body): array
    {
        try {
            $response = Http::baseUrl((string) config('jinlipay.base_url'))
                ->withHeaders(['X-API-KEY' => (string) config('jinlipay.api_key')])
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('jinlipay.timeout'))
                ->post($path, $body);
        } catch (ConnectionException $exception) {
            throw new JinlipayException('0001', 'Upstream connection failed.', [], 502);
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new JinlipayException('0001', 'Upstream returned an invalid response.', [], 502);
        }

        $code = (string) ($payload['code'] ?? '');
        if ($code !== '0000') {
            $message = (string) ($payload['messages']['en'] ?? $payload['message'] ?? 'Upstream request failed.');
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

            throw new JinlipayException($code !== '' ? $code : '0001', $message, $data, $response->status() ?: 400);
        }

        return $payload;
    }
}
