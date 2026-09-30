<?php

return [
    'labels' => [
        'MerchantApiIp' => '商户API白名单',
    ],
    'fields' => [
        'merchant_id' => '商户',
        'ip_address' => 'IP地址',
        'status' => '状态',
        'remark' => '备注',
        'created_at' => '创建时间',
        'updated_at' => '更新时间',
    ],
    'options' => [
        'status' => [
            0 => '停用',
            1 => '启用',
        ],
    ],
];
