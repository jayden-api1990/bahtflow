<?php

return [
    'labels' => [
        'MerchantCredit' => 'เครดิตร้านค้า',
        'adjust' => 'ปรับเครดิต',
        'logs' => 'บันทึกการเปลี่ยนแปลงเครดิต',
    ],
    'fields' => [
        'merchant_id' => 'ร้านค้า',
        'credit_balance' => 'ยอดเครดิต',
        'current_balance' => 'ยอดปัจจุบัน',
        'type' => 'ประเภทการปรับ',
        'amount' => 'จำนวนที่ปรับ',
        'note' => 'หมายเหตุ',
        'admin_user_id' => 'ผู้ดำเนินการ',
        'balance_before' => 'ยอดก่อนปรับ',
        'balance_after' => 'ยอดหลังปรับ',
        'created_at' => 'สร้างเมื่อ',
        'updated_at' => 'อัปเดตเมื่อ',
    ],
    'options' => [
        'type' => [
            'increase' => 'เพิ่ม',
            'decrease' => 'ลด',
        ],
    ],
    'messages' => [
        'adjust_success' => 'ปรับเครดิตร้านค้าสำเร็จ',
        'adjust_failed' => 'ปรับเครดิตร้านค้าไม่สำเร็จ',
        'insufficient_balance' => 'เครดิตร้านค้าไม่เพียงพอสำหรับการลดครั้งนี้',
    ],
];
