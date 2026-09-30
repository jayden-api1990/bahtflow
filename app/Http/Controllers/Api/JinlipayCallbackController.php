<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MerchantPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class JinlipayCallbackController extends Controller
{
    public function __construct(private readonly MerchantPaymentService $payments) {}

    public function deposit(Request $request): JsonResponse
    {
        return $this->ack(fn () => $this->payments->handleDepositCallback($request->all()));
    }

    public function withdraw(Request $request): JsonResponse
    {
        return $this->ack(fn () => $this->payments->handleWithdrawCallback($request->all()));
    }

    private function ack(callable $action): JsonResponse
    {
        try {
            $action();
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'ORDER_NOT_FOUND') {
                return response()->json([
                    'code' => '0002',
                    'message' => 'Order not found.',
                ], 404);
            }

            return response()->json([
                'code' => '0001',
                'message' => 'Callback failed.',
            ], 500);
        }

        return response()->json([
            'code' => '0000',
            'message' => 'success',
        ]);
    }
}
