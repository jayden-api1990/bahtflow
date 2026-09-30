<?php

namespace App\Services\Merchant;

class MerchantSignService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function sign(array $payload, string $md5key): string
    {
        unset($payload['sign']);

        return md5($this->buildSignBase($payload).$md5key);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function buildSignBase(array $payload): string
    {
        ksort($payload);

        $normalized = [];
        foreach ($payload as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                $value = $this->normalizeArrayValue($value);
            }

            $normalized[$key] = (string) $value;
        }

        return http_build_query($normalized, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function normalizeArrayValue(array $value): string
    {
        ksort($value);

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
