<?php

return [
    'labels' => [
        'MerchantCreditLog' => '商户额度日志',
    ],
    'fields' => [
        'merchant_id' => '商户',
        'merchant_credit_id' => '商户额度',
        'type' => '调整类型',
        'amount' => '调整金额',
        'balance_before' => '调整前余额',
        'balance_after' => '调整后余额',
        'note' => '备注',
        'admin_user_id' => '操作管理员',
        'created_at' => '创建时间',
        'updated_at' => '更新时间',
    ],
    'options' => [
        'type' => [
            'increase' => '增加',
            'decrease' => '减少',
        ],
    ],
];
