<?php

return [
    'recipient' => env('SCREEN_FEEDBACK_TO'),
    'disk' => env('SCREEN_FEEDBACK_DISK', 'local'),
    'queue' => env('SCREEN_FEEDBACK_QUEUE', 'default'),
    'route' => 'screen-feedback',
    'middleware' => ['web', 'auth'],
    'register_routes' => true,
    'types' => ['issue' => 'Issue', 'information' => 'Information'],
    'max_message_length' => 5000,
    'max_screenshot_bytes' => 2_000_000,
    'max_screenshot_pixels' => 16_000_000,
    'submissions_per_minute' => 5,
];
