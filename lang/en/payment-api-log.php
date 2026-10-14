<?php

return [
    'labels' => [
        'PaymentApiLog' => 'API Logs',
    ],
    'fields' => [
        'payment_order_id' => 'Order',
        'merchant_id' => 'Merchant',
        'direction' => 'Direction',
        'url' => 'URL',
        'request_body' => 'Request Body',
        'response_status' => 'HTTP Status',
        'response_body' => 'Response Body',
        'error' => 'Error',
        'created_at' => 'Time',
        'updated_at' => 'Updated At',
    ],
    'options' => [
        'direction' => [
            'merchant_request' => 'Merchant Request',
            'upstream' => 'Upstream Request',
            'upstream_callback' => 'Upstream Callback',
            'merchant_callback' => 'Merchant Callback',
        ],
    ],
];
