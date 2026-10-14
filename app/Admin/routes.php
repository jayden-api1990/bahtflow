<?php

use Dcat\Admin\Admin;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Admin::routes();

Route::group([
    'prefix' => config('admin.route.prefix'),
    'namespace' => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('auth/locale', 'AuthController@switchLocale');
    $router->get('/', 'HomeController@index');
    $router->resource('/member-user', MemberUserController::class);

    // 开放接口
    $router->get('openapi-docs', 'OpenApiDocsController@index');

    // 全局配置
    $router->get('web-config', 'WebConfigController@index');
    $router->post('web-config/save', 'WebConfigController@saveData');

    $router->resource('/merchant', MerchantController::class);
    $router->resource('/merchant-credit', MerchantCreditController::class);
    $router->resource('/merchant-credit-log', MerchantCreditLogController::class);
    $router->resource('/merchant-api-ip', MerchantApiIpController::class);
    $router->resource('/payment-deposit', PaymentDepositController::class);
    $router->resource('/payment-withdraw', PaymentWithdrawController::class);
    $router->resource('/payment-api-log', PaymentApiLogController::class);
    $router->get('payment-deposit-report', 'PaymentDepositReportController@index');
    $router->get('payment-deposit-report/export', 'PaymentDepositReportController@export');
    $router->get('payment-withdraw-report', 'PaymentWithdrawReportController@index');
    $router->get('payment-withdraw-report/export', 'PaymentWithdrawReportController@export');
    $router->get('merchant-credit/{merchant_credit}/adjust', 'MerchantCreditController@adjust');
    $router->post('merchant-credit/{merchant_credit}/adjust', 'MerchantCreditController@saveAdjust');
});
