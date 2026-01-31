<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pricing Plans
    |--------------------------------------------------------------------------
    |
    | Define the subscription plans available to tenants. Each plan
    | specifies pricing and feature limits.
    |
    */
    'plans' => [
        'free' => [
            'name' => 'Free',
            'description' => 'Perfect for trying out the platform',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'features' => [
                'forms' => 3,
                'submissions_per_month' => 100,
                'file_upload_size' => 5, // MB
                'team_members' => 1,
                'custom_domain' => false,
                'white_label' => false,
                'api_access' => false,
                'integrations' => false,
                'priority_support' => false,
                'form_logic' => false,
                'file_uploads' => true,
                'email_notifications' => true,
                'form_analytics' => false,
                'export_data' => false,
                'remove_branding' => false,
            ],
            'limits' => [
                'max_fields_per_form' => 10,
                'max_submissions_stored' => 100,
                'data_retention_days' => 30,
            ],
        ],

        'starter' => [
            'name' => 'Starter',
            'description' => 'Great for small businesses and freelancers',
            'price_monthly' => 19,
            'price_yearly' => 190, // Save ~17%
            'features' => [
                'forms' => 10,
                'submissions_per_month' => 1000,
                'file_upload_size' => 25, // MB
                'team_members' => 3,
                'custom_domain' => false,
                'white_label' => false,
                'api_access' => true,
                'integrations' => true,
                'priority_support' => false,
                'form_logic' => true,
                'file_uploads' => true,
                'email_notifications' => true,
                'form_analytics' => true,
                'export_data' => true,
                'remove_branding' => false,
            ],
            'limits' => [
                'max_fields_per_form' => 50,
                'max_submissions_stored' => 5000,
                'data_retention_days' => 90,
            ],
        ],

        'professional' => [
            'name' => 'Professional',
            'description' => 'For growing teams that need more power',
            'price_monthly' => 49,
            'price_yearly' => 490, // Save ~17%
            'popular' => true,
            'features' => [
                'forms' => 50,
                'submissions_per_month' => 10000,
                'file_upload_size' => 50, // MB
                'team_members' => 10,
                'custom_domain' => true,
                'white_label' => true,
                'api_access' => true,
                'integrations' => true,
                'priority_support' => true,
                'form_logic' => true,
                'file_uploads' => true,
                'email_notifications' => true,
                'form_analytics' => true,
                'export_data' => true,
                'remove_branding' => true,
                'custom_email_domain' => true,
                'webhooks' => true,
            ],
            'limits' => [
                'max_fields_per_form' => 100,
                'max_submissions_stored' => 50000,
                'data_retention_days' => 365,
            ],
        ],

        'enterprise' => [
            'name' => 'Enterprise',
            'description' => 'Advanced features for large organizations',
            'price_monthly' => 149,
            'price_yearly' => 1490,
            'features' => [
                'forms' => -1, // unlimited
                'submissions_per_month' => -1,
                'file_upload_size' => 100, // MB
                'team_members' => -1,
                'custom_domain' => true,
                'white_label' => true,
                'api_access' => true,
                'integrations' => true,
                'priority_support' => true,
                'form_logic' => true,
                'file_uploads' => true,
                'email_notifications' => true,
                'form_analytics' => true,
                'export_data' => true,
                'remove_branding' => true,
                'custom_email_domain' => true,
                'webhooks' => true,
                'sso' => true,
                'audit_log' => true,
                'dedicated_support' => true,
                'sla' => true,
                'custom_contracts' => true,
            ],
            'limits' => [
                'max_fields_per_form' => -1, // unlimited
                'max_submissions_stored' => -1,
                'data_retention_days' => -1, // unlimited
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */
    'currency' => env('BILLING_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Tax Settings
    |--------------------------------------------------------------------------
    */
    'tax' => [
        'enabled' => env('BILLING_TAX_ENABLED', false),
        'rate' => env('BILLING_TAX_RATE', 0),
        'inclusive' => env('BILLING_TAX_INCLUSIVE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trial Settings
    |--------------------------------------------------------------------------
    */
    'trial' => [
        'enabled' => true,
        'days' => 14,
        'plan' => 'professional', // Plan features during trial
        'require_card' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Overage Settings
    |--------------------------------------------------------------------------
    */
    'overage' => [
        'enabled' => false,
        'submission_rate' => 0.01, // Per submission over limit
        'notify_at_percentage' => 80, // Notify when 80% of limit used
    ],
];
