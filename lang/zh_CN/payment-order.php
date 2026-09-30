<?php

return [
    'labels' => [
        'PaymentOrder' => '支付订单',
    ],
    'fields' => [
        'merchant_id' => '商户',
        'type' => '类型',
        'merchant_order_no' => '商户单号',
        'upstream_ref' => '上游单号',
        'amount' => '金额',
        'status' => '状态',
        'callback_url' => '商户回调',
        'upload_url' => '凭证上传链接',
        'credit_locked' => '锁定额度',
        'credit_released' => '退回额度',
        'credit_applied' => '入账额度',
        'completed_amount' => '已完成金额',
        'remaining_amount' => '剩余金额',
        'request_payload' => '请求',
        'upstream_response' => '上游响应',
        'callback_payload' => '回调内容',
        'created_at' => '创建时间',
        'updated_at' => '更新时间',
    ],
    'options' => [
        'type' => [
            'deposit' => '存款',
            'withdraw' => '取款',
        ],
    ],
];
