<?php

return [
    'labels' => [
        'MerchantApiIp' => 'รายการ IP ที่อนุญาตของร้านค้า',
    ],
    'fields' => [
        'merchant_id' => 'ร้านค้า',
        'ip_address' => 'ที่อยู่ IP',
        'status' => 'สถานะ',
        'remark' => 'หมายเหตุ',
        'created_at' => 'สร้างเมื่อ',
        'updated_at' => 'อัปเดตเมื่อ',
    ],
    'options' => [
        'status' => [
            0 => 'ปิดใช้งาน',
            1 => 'เปิดใช้งาน',
        ],
    ],
];
