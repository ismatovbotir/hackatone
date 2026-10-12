<?php

return [
    // Reminders and "today" for the training plan are computed in this timezone.
    'timezone' => env('HH_TIMEZONE', 'Asia/Tashkent'),

    // Development only: skip the email / Telegram confirmation after registration.
    // Ignored unless APP_ENV=local, so it can never switch off confirmation in production.
    'skip_confirmation' => env('HH_SKIP_CONFIRMATION', false),

    // First admin, created or updated by the database seeder (php artisan db:seed).
    'admin' => [
        'name' => env('HH_ADMIN_NAME', 'Admin'),
        'email' => env('HH_ADMIN_EMAIL'),
        'password' => env('HH_ADMIN_PASSWORD'),
    ],

    // Largest activity GIF/MP4 an admin can upload, in KB. Must not exceed PHP's upload_max_filesize.
    'media_max_kb' => (int) env('HH_MEDIA_MAX_KB', 2048),

    // Largest AI-generated file we accept, in KB (downloaded by the server, so PHP's upload limit does not apply).
    'ai_media_max_kb' => 20480,

    'locales' => ['uz', 'en', 'ru'],
    'default_locale' => 'uz',

    'link_code_ttl_minutes' => 15,
    'link_attempts_per_chat' => 5,
    'link_attempts_decay_seconds' => 600,

    // Days between "measure your height" reminders.
    'measure_every_days' => 30,
];
