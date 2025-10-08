<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default TTL (Time To Live) in Hours
    |--------------------------------------------------------------------------
    |
    | How long temporary media files should be kept before being eligible
    | for cleanup. Files older than this will be automatically removed.
    |
    */
    'default_ttl_hours' => env('TEMP_MEDIA_TTL_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Maximum File Size
    |--------------------------------------------------------------------------
    |
    | The maximum file size allowed for temporary media uploads in bytes.
    | Default is 10MB.
    |
    */
    'max_file_size' => env('TEMP_MEDIA_MAX_SIZE', 10 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME Types
    |--------------------------------------------------------------------------
    |
    | Array of allowed MIME types for temporary media uploads.
    |
    */
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | The disk where temporary media files should be stored.
    | This should match one of your configured filesystems.
    |
    */
    'disk' => env('TEMP_MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Auto Cleanup
    |--------------------------------------------------------------------------
    |
    | Whether to automatically register the cleanup command in the scheduler.
    | When enabled, expired temporary media will be cleaned up hourly.
    |
    */
    'enable_auto_cleanup' => env('TEMP_MEDIA_AUTO_CLEANUP', true),

    /*
    |--------------------------------------------------------------------------
    | Cleanup Schedule Configuration
    |--------------------------------------------------------------------------
    |
    | Configure when and how the cleanup command should run.
    | Available frequencies: 'everyMinute', 'everyFiveMinutes', 'everyTenMinutes',
    | 'everyFifteenMinutes', 'everyThirtyMinutes', 'hourly', 'everyTwoHours',
    | 'everyThreeHours', 'everySixHours', 'daily', 'weekly', 'monthly'
    |
    */
    'cleanup_schedule' => [
        'frequency' => env('TEMP_MEDIA_CLEANUP_FREQUENCY', 'hourly'),
        'without_overlapping' => env('TEMP_MEDIA_CLEANUP_NO_OVERLAP', true),
        'run_in_background' => env('TEMP_MEDIA_CLEANUP_BACKGROUND', true),
        'timeout' => env('TEMP_MEDIA_CLEANUP_TIMEOUT', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Discovery
    |--------------------------------------------------------------------------
    |
    | Whether to automatically register routes and other components.
    | Disable this if you want to manually control package registration.
    |
    */
    'auto_discovery' => env('TEMP_MEDIA_AUTO_DISCOVERY', true),

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the package's API routes.
    |
    */
    'routes' => [
        'prefix' => 'api/temp-media',
        'middleware' => ['api'],
        'name_prefix' => 'temp-media.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Validation
    |--------------------------------------------------------------------------
    |
    | Whether to validate that temporary media belongs to the current session.
    | This adds an extra layer of security but may not be suitable for
    | stateless applications or API-only usage.
    |
    */
    'validate_session' => env('TEMP_MEDIA_VALIDATE_SESSION', true),

    /*
    |--------------------------------------------------------------------------
    | Media Conversions
    |--------------------------------------------------------------------------
    |
    | Whether to generate thumbnails and other conversions for temporary media.
    | This can be disabled to improve upload performance if conversions
    | will be generated later when media is transferred to the final model.
    |
    */
    'generate_conversions' => env('TEMP_MEDIA_GENERATE_CONVERSIONS', false),

    /*
    |--------------------------------------------------------------------------
    | Collection Name
    |--------------------------------------------------------------------------
    |
    | The default media collection name for temporary media files.
    |
    */
    'collection_name' => 'temp_files',

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Whether to dispatch events for temporary media operations.
    | Useful for logging, notifications, or custom business logic.
    |
    */
    'dispatch_events' => env('TEMP_MEDIA_DISPATCH_EVENTS', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue settings for background jobs like cleanup and media processing.
    |
    */
    'queue' => [
        'connection' => env('TEMP_MEDIA_QUEUE_CONNECTION', 'default'),
        'queue' => env('TEMP_MEDIA_QUEUE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration for temporary media uploads.
    |
    */
    'rate_limiting' => [
        'enabled' => env('TEMP_MEDIA_RATE_LIMIT', true),
        'max_attempts' => env('TEMP_MEDIA_RATE_LIMIT_ATTEMPTS', 60),
        'decay_minutes' => env('TEMP_MEDIA_RATE_LIMIT_DECAY', 1),
    ],
];
