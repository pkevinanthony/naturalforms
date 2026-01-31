<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Central Domain
    |--------------------------------------------------------------------------
    |
    | The main domain for the application. Tenant subdomains will be
    | created under this domain (e.g., tenant.yourdomain.com)
    |
    */
    'central_domain' => env('TENANT_CENTRAL_DOMAIN', 'app.formbuilder.local'),

    /*
    |--------------------------------------------------------------------------
    | Central Domains
    |--------------------------------------------------------------------------
    |
    | Domains that should not be treated as tenant domains. These are
    | typically the main application domains for registration, etc.
    |
    */
    'central_domains' => explode(',', env('TENANT_CENTRAL_DOMAINS', 'formbuilder.local,www.formbuilder.local,app.formbuilder.local')),

    /*
    |--------------------------------------------------------------------------
    | Reserved Subdomains
    |--------------------------------------------------------------------------
    |
    | Subdomains that cannot be used by tenants. These are typically
    | reserved for system use.
    |
    */
    'reserved_subdomains' => [
        'www',
        'api',
        'admin',
        'app',
        'mail',
        'smtp',
        'ftp',
        'cdn',
        'assets',
        'static',
        'media',
        'support',
        'help',
        'docs',
        'status',
        'blog',
    ],

    /*
    |--------------------------------------------------------------------------
    | Identification Method
    |--------------------------------------------------------------------------
    |
    | How to identify tenants. Options: 'subdomain', 'domain', 'path', 'header'
    |
    */
    'identification_method' => env('TENANT_IDENTIFICATION_METHOD', 'subdomain'),

    /*
    |--------------------------------------------------------------------------
    | Trial Period
    |--------------------------------------------------------------------------
    |
    | The number of days for the trial period when a new tenant is created.
    |
    */
    'trial_days' => env('TENANT_TRIAL_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Custom Domain Settings
    |--------------------------------------------------------------------------
    */
    'custom_domains' => [
        'enabled' => env('TENANT_CUSTOM_DOMAINS_ENABLED', true),
        'verification_method' => 'dns_txt', // dns_txt or http
        'verification_prefix' => '_formbuilder-verify',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Branding
    |--------------------------------------------------------------------------
    */
    'default_branding' => [
        'primary_color' => '#3B82F6',
        'secondary_color' => '#10B981',
        'font_family' => 'Inter',
        'footer_text' => 'Powered by FormBuilder',
        'hide_powered_by' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('TENANT_CACHE_ENABLED', true),
        'ttl' => env('TENANT_CACHE_TTL', 300), // 5 minutes
    ],
];
