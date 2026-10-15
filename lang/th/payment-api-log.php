<?php

return [
    'labels' => [
        'PaymentApiLog' => 'บันทึก API',
    ],
    'fields' => [
        'payment_order_id' => 'คำสั่ง',
        'merchant_id' => 'ร้านค้า',
        'direction' => 'ทิศทาง',
        'url' => 'URL',
        'request_body' => 'เนื้อหาคำขอ',
        'response_status' => 'สถานะ HTTP',
        'response_body' => 'เนื้อหาการตอบกลับ',
        'error' => 'ข้อผิดพลาด',
        'created_at' => 'เวลา',
        'updated_at' => 'อัปเดตเมื่อ',
    ],
    'options' => [
        'direction' => [
            'merchant_request' => 'คำขอจากร้านค้า',
            'upstream' => 'คำขอไปต้นทาง',
            'upstream_callback' => 'Callback จากต้นทาง',
            'merchant_callback' => 'Callback ไปร้านค้า',
        ],
    ],
];
