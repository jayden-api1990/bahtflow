<?php

return [
    'labels' => [
        'MerchantApiIp' => 'Merchant API IP Whitelist',
    ],
    'fields' => [
        'merchant_id' => 'Merchant',
        'ip_address' => 'IP Address',
        'status' => 'Status',
        'remark' => 'Remark',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],
    'options' => [
        'status' => [
            0 => 'Disabled',
            1 => 'Enabled',
        ],
    ],
];
