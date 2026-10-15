<?php

return [
    'labels' => [
        'Merchant' => 'ร้านค้า',
    ],
    'fields' => [
        'name' => 'ชื่อร้านค้า',
        'merchant_serial' => 'รหัสร้านค้า',
        'md5key' => 'คีย์ MD5',
        'status' => 'สถานะ',
        'callback_url' => 'URL Callback',
        'currency' => 'สกุลเงิน',
        'deposit_fee' => 'ค่าธรรมเนียมฝาก',
        'withdraw_fee' => 'ค่าธรรมเนียมถอน',
        'admin_id' => 'ผู้ดูแล',
        'admin_users_count' => 'จำนวนผู้ดูแล',
    ],
    'options' => [
        'status' => [
            0 => 'ปิดใช้งาน',
            1 => 'เปิดใช้งาน',
        ],
    ],
];
