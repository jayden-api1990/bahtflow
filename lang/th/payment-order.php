<?php

return [
    'labels' => [
        'PaymentOrder' => 'คำสั่งชำระเงิน',
    ],
    'fields' => [
        'merchant_id' => 'ร้านค้า',
        'type' => 'ประเภท',
        'order_no' => 'เลขที่คำสั่ง',
        'merchant_order_no' => 'เลขที่คำสั่งร้านค้า',
        'upstream_ref' => 'เลขที่คำสั่งระบบ',
        'amount' => 'จำนวนเงิน',
        'payer' => 'ผู้ชำระ',
        'matched_withdraw' => 'บัญชีปลายทาง (จับคู่ถอน)',
        'payee' => 'ผู้รับเงิน',
        'amount_fee' => 'ค่าธรรมเนียม',
        'status' => 'สถานะ',
        'callback_url' => 'Callback ของร้านค้า',
        'request_payload' => 'ข้อมูลคำขอ',
        'upstream_response' => 'การตอบกลับต้นทาง',
        'upload_url' => 'URL อัปโหลด',
        'credit_locked' => 'เครดิตที่ล็อก',
        'credit_released' => 'เครดิตที่ปล่อย',
        'credit_applied' => 'เครดิตที่ใช้แล้ว',
        'completed_amount' => 'ยอดที่เสร็จสิ้น',
        'remaining_amount' => 'ยอดคงเหลือ',
        'created_at' => 'สร้างเมื่อ',
        'updated_at' => 'อัปเดตเมื่อ',
    ],
    'options' => [
        'type' => [
            'deposit' => 'ฝากเงิน',
            'withdraw' => 'ถอนเงิน',
        ],
        'status' => [
            'processing' => 'กำลังดำเนินการ',
            'pending' => 'รอดำเนินการ',
            'update' => 'กำลังอัปเดต',
            'complete' => 'สำเร็จ',
            'complete_below_min' => 'สำเร็จต่ำกว่าขั้นต่ำ',
            'failed' => 'ล้มเหลว',
            'cancelled' => 'ยกเลิก',
            'cancelled_by_admin' => 'ยกเลิกโดยผู้ดูแล',
            'timeout' => 'หมดเวลา',
        ],
    ],
];
