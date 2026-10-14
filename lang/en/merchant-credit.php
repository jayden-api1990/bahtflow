<?php

return [
    'labels' => [
        'MerchantCredit' => 'Merchant Credit',
        'adjust' => 'Adjust Credit',
        'logs' => 'Credit Change Logs',
    ],
    'fields' => [
        'merchant_id' => 'Merchant',
        'credit_balance' => 'Credit Balance',
        'current_balance' => 'Current Balance',
        'type' => 'Adjustment Type',
        'amount' => 'Adjustment Amount',
        'note' => 'Note',
        'admin_user_id' => 'Operator',
        'balance_before' => 'Balance Before',
        'balance_after' => 'Balance After',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],
    'options' => [
        'type' => [
            'increase' => 'Increase',
            'decrease' => 'Decrease',
        ],
    ],
    'messages' => [
        'adjust_success' => 'Merchant credit adjusted successfully.',
        'adjust_failed' => 'Failed to adjust merchant credit.',
        'insufficient_balance' => 'Insufficient merchant credit for this decrease.',
    ],
];
