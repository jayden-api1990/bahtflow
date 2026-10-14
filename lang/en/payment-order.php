<?php

return [
    'labels' => [
        'PaymentOrder' => 'Payment Orders',
    ],
    'fields' => [
        'merchant_id' => 'Merchant',
        'type' => 'Type',
        'order_no' => 'Order No.',
        'merchant_order_no' => 'Merchant Order No.',
        'upstream_ref' => 'System Order No.',
        'amount' => 'Amount',
        'payer' => 'Payer',
        'matched_withdraw' => 'Target (Matched Withdraw)',
        'payee' => 'Payee',
        'amount_fee' => 'Fee',
        'status' => 'Status',
        'callback_url' => 'Merchant Callback',
        'request_payload' => 'Request Payload',
        'upstream_response' => 'Upstream Response',
        'upload_url' => 'Upload URL',
        'credit_locked' => 'Locked Credit',
        'credit_released' => 'Released Credit',
        'credit_applied' => 'Applied Credit',
        'completed_amount' => 'Completed Amount',
        'remaining_amount' => 'Remaining Amount',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],
    'options' => [
        'type' => [
            'deposit' => 'Deposit',
            'withdraw' => 'Withdraw',
        ],
        'status' => [
            'processing' => 'Processing',
            'pending' => 'Pending',
            'update' => 'Updating',
            'complete' => 'Complete',
            'complete_below_min' => 'Complete Below Min',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'cancelled_by_admin' => 'Cancelled by Admin',
            'timeout' => 'Timeout',
        ],
    ],
];
