<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');
    $router->resource('/member-user', MemberUserController::class);

    # 开放接口
    $router->get('openapi-docs', 'OpenApiDocsController@index');

    # 全局配置
    $router->get('web-config', 'WebConfigController@index');
    $router->post('web-config/save', 'WebConfigController@saveData');

    $router->resource('/merchant', MerchantController::class);
    $router->resource('/merchant-credit', MerchantCreditController::class);
    $router->resource('/merchant-credit-log', MerchantCreditLogController::class);
    $router->resource('/merchant-api-ip', MerchantApiIpController::class);
    $router->resource('/payment-order', PaymentOrderController::class);
    $router->get('merchant-credit/{merchant_credit}/adjust', 'MerchantCreditController@adjust');
    $router->post('merchant-credit/{merchant_credit}/adjust', 'MerchantCreditController@saveAdjust');
});
