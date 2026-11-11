<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Database 3: AllegroGraph repository untuk data semantik tempat wisata.
    'sparql' => [
        'driver' => env('SPARQL_DRIVER', 'allegrograph'),
        'host' => env('SPARQL_HOST'),
        'database' => env('SPARQL_DATABASE'),
        'username' => env('SPARQL_USERNAME'),
        'password' => env('SPARQL_PASSWORD'),
        'endpoint' => env('SPARQL_ENDPOINT'),
        'statements_endpoint' => env('SPARQL_STATEMENTS_ENDPOINT', env('SPARQL_HOST').'/repositories/'.env('SPARQL_DATABASE').'/statements'),
        'timeout' => (int) env('SPARQL_TIMEOUT', 30),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'supabase' => [
        'katalog' => [
            'url' => env('SUPABASE_DB1_URL'),
            'publishable_key' => env('SUPABASE_DB1_PUBLISHABLE_KEY'),
            'secret_key' => env('SUPABASE_DB1_SECRET_KEY'),
            'jwks_url' => env('SUPABASE_DB1_JWKS_URL'),
        ],
        'transaksi' => [
            'url' => env('SUPABASE_DB2_URL'),
            'publishable_key' => env('SUPABASE_DB2_PUBLISHABLE_KEY'),
            'secret_key' => env('SUPABASE_DB2_SECRET_KEY'),
            'jwks_url' => env('SUPABASE_DB2_JWKS_URL'),
        ],
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
