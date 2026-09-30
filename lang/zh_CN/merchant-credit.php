<?php

return [
    'labels' => [
        'MerchantCredit' => '商户额度',
        'adjust' => '调整额度',
        'logs' => '额度变动记录',
    ],
    'fields' => [
        'merchant_id' => '商户',
        'credit_balance' => '额度余额',
        'current_balance' => '当前额度',
        'type' => '调整类型',
        'amount' => '调整金额',
        'note' => '备注',
        'admin_user_id' => '操作管理员',
        'balance_before' => '调整前余额',
        'balance_after' => '调整后余额',
        'created_at' => '创建时间',
        'updated_at' => '更新时间',
    ],
    'options' => [
        'type' => [
            'increase' => '增加',
            'decrease' => '减少',
        ],
    ],
    'messages' => [
        'adjust_success' => '商户额度调整成功。',
        'adjust_failed' => '商户额度调整失败。',
        'insufficient_balance' => '商户额度不足，无法减少。',
    ],
];
