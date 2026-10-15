<?php

return [
    'labels' => [
        'MerchantCreditLog' => 'บันทึกเครดิตร้านค้า',
    ],
    'fields' => [
        'merchant_id' => 'ร้านค้า',
        'merchant_credit_id' => 'เครดิตร้านค้า',
        'type' => 'ประเภทการปรับ',
        'amount' => 'จำนวนที่ปรับ',
        'balance_before' => 'ยอดก่อนปรับ',
        'balance_after' => 'ยอดหลังปรับ',
        'note' => 'หมายเหตุ',
        'admin_user_id' => 'ผู้ดำเนินการ',
        'created_at' => 'สร้างเมื่อ',
        'updated_at' => 'อัปเดตเมื่อ',
    ],
    'options' => [
        'type' => [
            'increase' => 'เพิ่ม',
            'decrease' => 'ลด',
        ],
    ],
];
