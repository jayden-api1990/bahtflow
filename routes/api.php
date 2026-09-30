<?php

use App\Http\Controllers\Api\JinlipayCallbackController;
use App\Http\Controllers\Api\MerchantPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'respCode' => '0000',
        'respMsg' => 'ok',
        'data' => ['service' => 'jlpay'],
    ]);
});

Route::post('/jinlipay/deposit-callback', [JinlipayCallbackController::class, 'deposit']);
Route::post('/jinlipay/withdraw-callback', [JinlipayCallbackController::class, 'withdraw']);

Route::prefix('v1')->middleware(['merchant.sign', 'merchant.ip'])->group(function () {
    Route::post('/deposit', [MerchantPaymentController::class, 'deposit']);
    Route::post('/deposit/cancel', [MerchantPaymentController::class, 'cancelDeposit']);
    Route::post('/withdraw', [MerchantPaymentController::class, 'withdraw']);
    Route::post('/balance', [MerchantPaymentController::class, 'balance']);
});
