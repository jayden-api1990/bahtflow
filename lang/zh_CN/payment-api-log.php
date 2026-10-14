<?php

return [
    'labels' => [
        'PaymentApiLog' => '接口日志',
    ],
    'fields' => [
        'payment_order_id' => '订单',
        'merchant_id' => '商户',
        'direction' => '方向',
        'url' => '地址',
        'request_body' => '请求参数',
        'response_status' => 'HTTP状态',
        'response_body' => '返回数据',
        'error' => '错误',
        'created_at' => '时间',
        'updated_at' => '更新时间',
    ],
    'options' => [
        'direction' => [
            'merchant_request' => '商户请求',
            'upstream' => '请求上游',
            'upstream_callback' => '上游回调',
            'merchant_callback' => '回商户',
        ],
    ],
];
