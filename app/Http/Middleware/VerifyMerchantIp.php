<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use App\Models\MerchantApiIp;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyMerchantIp
{
    public function handle(Request $request, Closure $next): mixed
    {
        $merchant = $request->attributes->get('merchant');

        if (! $merchant instanceof Merchant) {
            return $this->forbidden('Merchant not resolved.');
        }

        $clientIp = $request->resolvedClientIp();

        if (! MerchantApiIp::isAllowed((int) $merchant->id, $clientIp)) {
            return $this->forbidden('IP address not whitelisted.'.$clientIp);
        }

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
