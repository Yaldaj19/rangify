<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'default_model' => env('OPENROUTER_DEFAULT_MODEL', 'google/gemini-2.5-flash'),
        'proxy' => env('OPENROUTER_PROXY'),
    ],

    'opencv' => [
        'url' => env('OPENCV_SERVICE_URL', 'http://127.0.0.1:8001'),
        'timeout' => (int) env('OPENCV_TIMEOUT', 30),
        'default_method' => env('OPENCV_DEFAULT_METHOD', 'grabcut'),
    ],

    'replicate' => [
        'key' => env('REPLICATE_API_TOKEN'),
        'sam_model' => env('REPLICATE_SAM_MODEL', 'meta/sam-2'),
        'proxy' => env('REPLICATE_PROXY'),
    ],

    'huggingface' => [
        'key' => env('HUGGINGFACE_API_KEY'),
        'sam_model' => env('HUGGINGFACE_SAM_MODEL', 'facebook/sam-vit-base'),
        'proxy' => env('HUGGINGFACE_PROXY'),
    ],

];
