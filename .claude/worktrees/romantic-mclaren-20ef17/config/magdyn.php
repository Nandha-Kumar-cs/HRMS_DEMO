<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Identity
    |--------------------------------------------------------------------------
    */
    'app_name'    => env('MAGDYN_APP_NAME', 'HRMS'),
    'app_version' => env('MAGDYN_APP_VERSION', '2.0.0'),
    'company'     => env('MAGDYN_COMPANY', 'MagDyn Pvt Ltd'),
    'app_base'    => env('APP_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Single Sign-On (SSO)
    |--------------------------------------------------------------------------
    | Set MAGDYN_SSO_ENABLED=true and configure the provider details to enable
    | SSO login. The provider issues a JWT which this app verifies.
    */
    'sso' => [
        'enabled'       => env('MAGDYN_SSO_ENABLED', false),
        'provider_url'  => env('MAGDYN_SSO_URL', 'https://magdyn.com/SSO/sso-server'),
        'client_id'     => env('MAGDYN_SSO_CLIENT_ID', ''),
        'client_secret' => env('MAGDYN_SSO_CLIENT_SECRET', ''),
        'redirect_uri'  => env('MAGDYN_SSO_REDIRECT', env('APP_URL', '') . '/sso/callback'),
        'jwt_secret'    => env('MAGDYN_JWT_SECRET', ''),

        /*
         * Endpoint paths (relative to provider_url).
         * These match the MagDyn SSO server's OAuth2 routes.
         * Update if the server version uses different paths.
         */
        'endpoints' => [
            'authorize'   => '/authorize.php',          // SSOClient: /authorize.php
            'token'       => '/api/token.php',          // SSOClient: /api/token.php
            'userinfo'    => '/api/userinfo.php',       // SSOClient: /api/userinfo.php
            'logout'      => '/logout.php',             // SSOClient: /logout.php?redirect=...
            'sync'        => '/api/register_permissions.php',  // Manifest sync endpoint
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PWA / Push Notifications
    |--------------------------------------------------------------------------
    */
    'pwa' => [
        'enabled'          => env('MAGDYN_PWA_ENABLED', true),
        'vapid_public_key'  => env('VAPID_PUBLIC_KEY', ''),
        'vapid_private_key' => env('VAPID_PRIVATE_KEY', ''),
        'vapid_subject'     => env('VAPID_SUBJECT', 'mailto:admin@magdyn.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles — default system roles seeded on fresh install
    |--------------------------------------------------------------------------
    */
    'default_roles' => [
        ['name' => 'Admin',   'slug' => 'admin',   'description' => 'Full system access'],
        ['name' => 'Manager', 'slug' => 'manager', 'description' => 'Manage team & payroll'],
        ['name' => 'Staff',   'slug' => 'staff',   'description' => 'View own records'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules — used for permissions & mobile access settings
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'dashboard'         => 'Dashboard',
        'employees'         => 'Employees',
        'payroll'           => 'Payroll / Salary Slips',
        'attendance'        => 'Attendance',
        'leaves'            => 'Leave Requests',
        'loans'             => 'Loans & Advances',
        'increments'        => 'Increments & Promotions',
        'assets'            => 'Company Assets',
        'documents'         => 'Employee Documents',
        'benefits'          => 'Employee Benefits',
        'bonuses'           => 'Bonuses & Incentives',
        'reports'           => 'Reports',
        'training'          => 'Training',
        'settings'          => 'Settings',
        'users'             => 'User Management',
        'roles'             => 'Roles & Permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | DB Config file path (relative to base_path)
    |--------------------------------------------------------------------------
    | Place your production DB credentials in this file. It is .gitignored.
    | Copy db.local.example.php → db.local.php and fill in.
    */
    'db_config_file' => env('MAGDYN_DB_CONFIG', 'config/db.local.php'),

];
