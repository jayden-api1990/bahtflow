<?php

return [
    'labels' => [
        'Merchant' => '商户管理',
    ],
    'fields' => [
        'name' => '商户名称',
        'merchant_serial' => '商户序号',
        'md5key' => '加密金钥',
        'status' => '状态',
        'callback_url' => '回调地址',
        'currency' => '货币',
        'admin_id' => '管理员',
        'admin_users_count' => '管理员数',
    ],
    'options' => [
        'status' => [
            0 => '停用',
            1 => '启用',
        ],
    ],
];
