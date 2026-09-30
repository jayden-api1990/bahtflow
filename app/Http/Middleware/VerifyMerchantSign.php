<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use App\Services\Merchant\MerchantSignService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyMerchantSign
{
    public function handle(Request $request, Closure $next): mixed
    {
        $merchantSerial = (string) $request->input('merchant_serial', '');
        $requestSign = (string) $request->input('sign', '');

        if ($merchantSerial === '' || $requestSign === '') {
            return $this->forbidden('Missing merchant_serial or sign.');
        }

        $merchant = Merchant::query()
            ->where('merchant_serial', $merchantSerial)
            ->first();

        if (! $merchant || (int) $merchant->status !== 1) {
            return $this->forbidden('Invalid merchant_serial.');
        }

        $calculatedSign = app(MerchantSignService::class)->sign($request->except(['sign']), (string) $merchant->md5key);

        if (! hash_equals(strtolower($requestSign), strtolower($calculatedSign))) {
            return $this->forbidden('Invalid sign.');
        }

        $request->attributes->set('merchant', $merchant);

        return $next($request);
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json([
            'respCode' => '9999',
            'respMsg' => $message,
        ], 403);
    }
}
