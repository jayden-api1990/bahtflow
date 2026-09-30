<?php

return [
    'base_url' => rtrim((string) env('JINLIPAY_BASE_URL', 'https://jlapi-v1-stg.loooopool.io'), '/'),
    'api_key' => env('JINLIPAY_API_KEY', ''),
    'deposit_key' => env('JINLIPAY_DEPOSIT_KEY', ''),
    'deposit_secret' => env('JINLIPAY_DEPOSIT_SECRET', ''),
    'withdraw_key' => env('JINLIPAY_WITHDRAW_KEY', ''),
    'withdraw_secret' => env('JINLIPAY_WITHDRAW_SECRET', ''),
    'public_url' => rtrim((string) env('JINLIPAY_PUBLIC_URL', env('APP_URL', 'http://localhost')), '/'),
    'timeout' => (int) env('JINLIPAY_TIMEOUT', 20),
];
