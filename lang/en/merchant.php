<?php

return [
    'labels' => [
        'Merchant' => 'Merchants',
    ],
    'fields' => [
        'name' => 'Merchant Name',
        'merchant_serial' => 'Merchant Serial',
        'md5key' => 'MD5 Key',
        'status' => 'Status',
        'callback_url' => 'Callback URL',
        'currency' => 'Currency',
        'deposit_fee' => 'Deposit Fee',
        'withdraw_fee' => 'Withdraw Fee',
        'admin_id' => 'Admin',
        'admin_users_count' => 'Admins',
    ],
    'options' => [
        'status' => [
            0 => 'Disabled',
            1 => 'Enabled',
        ],
    ],
];
