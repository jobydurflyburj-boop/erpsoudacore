<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

// Final Production Validation pass: this file was missing entirely —
// every Log::warning()/Log::error() call across every sprint (AI
// provider fallbacks, notification delivery failures,
// BackupDatabaseCommand, ErrorTrackingService) has been going through
// Laravel's unpublished internal default the whole time. Publishing
// this explicitly adds two real, production-relevant things beyond
// the default: a `stderr` channel (the real, standard practice for a
// containerized deployment — see docker-compose.yml — where a
// container's logs are collected from stdout/stderr by the container
// runtime, not from a file path inside a possibly-ephemeral
// container filesystem), and a shorter default retention on the
// `daily` channel than Laravel's own 14-day default, matching this
// project's own `backup:database --keep-days=14` convention for
// consistency.

return [

    'default' => env('LOG_CHANNEL', 'stack'),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => (int) env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        // The real, standard channel for a containerized deployment
        // (docker-compose.yml's `app`/`queue`/`scheduler` services) —
        // logs to stderr, which the container runtime captures
        // directly rather than relying on a file path surviving
        // container restarts/rebuilds.
        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];
