<?php

// Final Production Validation pass: this file was missing entirely,
// relying on Laravel's unpublished internal default. Publishing it
// explicitly adds one real thing beyond the framework default: an
// 's3' disk definition, ready to configure for real off-host backup
// replication — docs/BACKUP_RESTORE_GUIDE.md names this as a real,
// necessary follow-up step ("copy these files off-host on a real
// schedule") that this project's own backup:database command
// deliberately does not do itself. This disk definition doesn't make
// that happen automatically — it makes it a config change away
// instead of a code change away, for whoever wires up that
// replication.

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // Ready for real off-host backup replication (see
        // docs/BACKUP_RESTORE_GUIDE.md) or any future module that
        // needs to store real user-uploaded files off the application
        // server — not wired into any code path yet; using it means
        // setting the AWS_* env vars below and pointing the relevant
        // Storage::disk() call at 's3'.
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'), // set this for any S3-compatible provider (DigitalOcean Spaces, MinIO, etc.), not AWS-exclusive
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
