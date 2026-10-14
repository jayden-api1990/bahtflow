<?php

return [
    'labels' => [
        'PaymentOrder' => '支付订单',
    ],
    'fields' => [
        'merchant_id' => '商户',
        'type' => '类型',
        'order_no' => '单号',
        'merchant_order_no' => '商户单号',
        'upstream_ref' => '系统单号',
        'amount' => '金额',
        'payer' => '付款方',
        'matched_withdraw' => '目标(匹配提款)',
        'payee' => '收款方',
        'amount_fee' => '手续费',
        'status' => '状态',
        'callback_url' => '商户回调',
        'request_payload' => '请求资料',
        'upstream_response' => '上游返回',
        'upload_url' => '凭证上传链接',
        'credit_locked' => '锁定额度',
        'credit_released' => '退回额度',
        'credit_applied' => '入账额度',
        'completed_amount' => '已完成金额',
        'remaining_amount' => '剩余金额',
        'created_at' => '创建时间',
        'updated_at' => '更新时间',
    ],
    'options' => [
        'type' => [
            'deposit' => '存款',
            'withdraw' => '取款',
        ],
        'status' => [
            'processing' => '处理中',
            'pending' => '待处理',
            'update' => '更新中',
            'complete' => '完成',
            'complete_below_min' => '低于最小额完成',
            'failed' => '失败',
            'cancelled' => '已取消',
            'cancelled_by_admin' => '管理员取消',
            'timeout' => '超时',
        ],
    ],
];
