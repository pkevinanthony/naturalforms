<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NMI Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the NMI payment gateway integration using Collect.js
    | for tokenization and the Transaction API for processing.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Your private API security key from NMI. This should be kept secret
    | and never exposed to the client-side.
    |
    */
    'api_key' => env('NMI_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Tokenization Key
    |--------------------------------------------------------------------------
    |
    | Your public tokenization key for Collect.js. This is safe to expose
    | on the client-side and is used to securely tokenize payment data.
    |
    */
    'tokenization_key' => env('NMI_TOKENIZATION_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Test Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, transactions will be processed in test mode.
    | Set to false for production.
    |
    */
    'test_mode' => env('NMI_TEST_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Secret key for validating webhook signatures from NMI.
    |
    */
    'webhook_secret' => env('NMI_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    */
    'endpoints' => [
        'transaction' => 'https://secure.networkmerchants.com/api/transact.php',
        'query' => 'https://secure.networkmerchants.com/api/query.php',
    ],

    /*
    |--------------------------------------------------------------------------
    | Collect.js Settings
    |--------------------------------------------------------------------------
    */
    'collectjs' => [
        'script_url' => 'https://secure.nmi.com/token/Collect.js',
        'variant' => 'inline', // inline or lightbox
        'timeout' => 30000, // milliseconds
        'currency' => 'USD',
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer Vault Settings
    |--------------------------------------------------------------------------
    */
    'vault' => [
        'enabled' => true,
        'store_cards' => true, // Store cards for future use
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'max_attempts' => 3,
        'retry_delay' => 1000, // milliseconds
        'failed_payment_retry_days' => [1, 3, 7], // Days to retry after failure
    ],
];
