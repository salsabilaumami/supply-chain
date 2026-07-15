<?php

return [

   

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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

   

    'rest_countries' => [
        'base_url' => env(
            'REST_COUNTRIES_BASE_URL',
            'https://api.restcountries.com/countries/v5'
        ),
        'key' => env('REST_COUNTRIES_API_KEY'),
    ],

    

    'open_meteo' => [
        'base_url' => env(
            'OPEN_METEO_BASE_URL',
            'https://api.open-meteo.com/v1'
        ),
    ],

    

    'world_bank' => [
        'base_url' => env(
            'WORLD_BANK_BASE_URL',
            'https://api.worldbank.org/v2'
        ),
    ],

   

    'exchange_rate' => [
        'base_url' => env(
            'EXCHANGE_RATE_BASE_URL',
            'https://v6.exchangerate-api.com/v6'
        ),
        'api_key' => env('EXCHANGE_RATE_API_KEY'),
    ],

    

    'gnews' => [
        'base_url' => env(
            'GNEWS_BASE_URL',
            'https://gnews.io/api/v4'
        ),
        'api_key' => env('GNEWS_API_KEY'),
    ],

    

    'overpass' => [
        'endpoints' => [
            env('OVERPASS_ENDPOINT_1', 'https://overpass.kumi.systems/api/interpreter'),
            env('OVERPASS_ENDPOINT_2', 'https://overpass-api.de/api/interpreter'),
            env('OVERPASS_ENDPOINT_3', 'https://overpass.openstreetmap.fr/api/interpreter'),
        ],
    ],

   

    'world_port_index' => [
        'dataset_path' => env(
            'WORLD_PORT_INDEX_DATASET_PATH',
            storage_path('app/datasets/world_port_index.csv')
        ),
    ],

];