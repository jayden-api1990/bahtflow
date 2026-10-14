<?php

return [
    'labels' => [
        'MerchantCreditLog' => 'Merchant Credit Logs',
    ],
    'fields' => [
        'merchant_id' => 'Merchant',
        'merchant_credit_id' => 'Merchant Credit',
        'type' => 'Adjustment Type',
        'amount' => 'Adjustment Amount',
        'balance_before' => 'Balance Before',
        'balance_after' => 'Balance After',
        'note' => 'Note',
        'admin_user_id' => 'Operator',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],
    'options' => [
        'type' => [
            'increase' => 'Increase',
            'decrease' => 'Decrease',
        ],
    ],
];
