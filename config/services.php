<?php

return [
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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('APP_URL') . '/auth/google/callback',

        'calendar' => [
            'enabled' => (bool) env(
                'GOOGLE_CALENDAR_ENABLED',
                false
            ),

            'auto_sync_enabled' => (bool) env(
                'GOOGLE_CALENDAR_AUTO_SYNC_ENABLED',
                false
            ),

            'organizer_user_id' => (int) env(
                'GOOGLE_CALENDAR_ORGANIZER_USER_ID',
                0
            ),

            'access_token' => env('GOOGLE_ACCESS_TOKEN'),

            'calendar_id' => env(
                'GOOGLE_CALENDAR_ID',
                'primary'
            ),
        ],

        'drive' => [
            'enabled' => (bool) env(
                'GOOGLE_DRIVE_ENABLED',
                false
            ),

            'organizer_user_id' => (int) env(
                'GOOGLE_DRIVE_ORGANIZER_USER_ID',
                env('GOOGLE_CALENDAR_ORGANIZER_USER_ID', 0)
            ),

            'root_folder_id' => env(
                'GOOGLE_DRIVE_ROOT_FOLDER_ID'
            ),

            'access_token' => env('GOOGLE_ACCESS_TOKEN'),
        ],
    ],
];