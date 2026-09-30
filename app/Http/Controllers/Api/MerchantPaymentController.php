<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CancelDepositRequest;
use App\Http\Requests\Api\CreateDepositRequest;
use App\Http\Requests\Api\CreateWithdrawRequest;
use App\Models\Merchant;
use App\Services\MerchantPaymentService;
use App\Services\PaymentGateway\JinlipayException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MerchantPaymentController extends Controller
{
    public function __construct(private readonly MerchantPaymentService $payments) {}

    public function deposit(CreateDepositRequest $request): JsonResponse
    {
        return $this->run(fn () => $this->payments->createDeposit($this->merchant($request), $request->validated()));
    }

    public function cancelDeposit(CancelDepositRequest $request): JsonResponse
    {
        return $this->run(fn () => $this->payments->cancelDeposit(
            $this->merchant($request),
            (string) $request->validated('merchantOrderNo'),
        ));
    }

    public function withdraw(CreateWithdrawRequest $request): JsonResponse
    {
        return $this->run(fn () => $this->payments->createWithdraw($this->merchant($request), $request->validated()));
    }

    public function balance(Request $request): JsonResponse
    {
        return $this->run(fn () => $this->payments->balance($this->merchant($request)));
    }

    private function merchant(Request $request): Merchant
    {
        $merchant = $request->attributes->get('merchant');

        if (! $merchant instanceof Merchant) {
            throw new RuntimeException('MERCHANT_DISABLED');
        }

        return $merchant;
    }

    private function run(callable $action): JsonResponse
    {
        try {
            return response()->json([
                'respCode' => '0000',
                'respMsg' => 'success',
                'data' => $action(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json([
                'respCode' => '0002',
                'respMsg' => 'merchantOrderNo already exists.',
            ], 422);
        } catch (JinlipayException $exception) {
            return response()->json([
                'respCode' => '9999',
                'respMsg' => $exception->getMessage(),
                'data' => [
                    'upstreamCode' => $exception->upstreamCode,
                    'upstreamData' => $exception->data,
                ],
            ], 400);
        } catch (RuntimeException $exception) {
            $message = match ($exception->getMessage()) {
                'INSUFFICIENT_BALANCE' => 'Insufficient merchant credit.',
                'ORDER_NOT_FOUND' => 'Order not found.',
                'ORDER_FINAL' => 'Order is already final.',
                'CALLBACK_URL_REQUIRED' => 'callbackUrl is required.',
                'MERCHANT_DISABLED' => 'Merchant is disabled.',
                default => 'Request failed.',
            };

            return response()->json([
                'respCode' => '9999',
                'respMsg' => $message,
            ], 400);
        }
    }
}
