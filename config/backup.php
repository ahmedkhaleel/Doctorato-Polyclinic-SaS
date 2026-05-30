<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

return [

    'backup' => [
        'name' => env('APP_NAME', 'Aura Derma Clinic'),

        'source' => [
            'files' => [
                'include' => [
                    // Only backup essential files, not the entire codebase
                    storage_path('app/public'),   // Uploaded files (photos, X-rays, documents)
                    base_path('.env'),             // Environment config
                ],

                'exclude' => [
                    storage_path('app/backup-temp'),
                    storage_path('framework'),
                    storage_path('logs'),
                ],

                'follow_links' => false,
                'ignore_unreadable_directories' => true,
                'relative_path' => base_path(),
            ],

            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        // Compress DB dumps with gzip for smaller backup size
        'database_dump_compressor' => \Spatie\DbDumper\Compressors\GzipCompressor::class,

        'database_dump_file_timestamp_format' => 'Y-m-d-H-i-s',

        'database_dump_filename_base' => 'database',

        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 9,
            'filename_prefix' => 'aura-clinic-',

            'disks' => [
                'local',                          // Local storage (always keep a copy)
                // 's3',                           // Uncomment when S3/cloud storage is configured
            ],

            'continue_on_failure' => true,        // Don't fail if one disk is down
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => env('BACKUP_ARCHIVE_PASSWORD'),

        'encryption' => 'default',

        'verify_backup' => true,                  // Verify backup integrity after creation

        'tries' => 3,                             // Retry on failure
        'retry_delay' => 30,                      // Wait 30 seconds between retries
    ],

    'notifications' => [
        // Only notify on the events that need a human to act. A daily full
        // backup + every-6h DB backup + daily cleanup would otherwise email
        // ~6 "success/healthy" messages a day — training the admin to ignore
        // the inbox and miss the failure alerts that actually matter. Keep
        // failures + unhealthy only. (Success confirmation is visible on the
        // admin Backups page and via backup:monitor.)
        'notifications' => [
            BackupHasFailedNotification::class => ['mail'],
            UnhealthyBackupWasFoundNotification::class => ['mail'],
            CleanupHasFailedNotification::class => ['mail'],
            BackupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_EMAIL', 'admin@auraderma.com'),

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'system@auraderma.com'),
                'name' => env('MAIL_FROM_NAME', 'Aura Derma Clinic'),
            ],
        ],

        'slack' => [
            'webhook_url' => env('BACKUP_SLACK_WEBHOOK', ''),
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],

        'webhook' => [
            'url' => '',
        ],
    ],

    'log_channel' => null,

    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'Aura Derma Clinic'),
            'disks' => ['local'],
            'health_checks' => [
                MaximumAgeInDays::class => 1,              // Alert if no backup in 24h
                MaximumStorageInMegabytes::class => 10000,  // Alert if > 10GB
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            // Medical clinic retention policy
            'keep_all_backups_for_days' => 7,          // All backups for 1 week
            'keep_daily_backups_for_days' => 30,       // Daily for 1 month
            'keep_weekly_backups_for_weeks' => 12,     // Weekly for 3 months
            'keep_monthly_backups_for_months' => 12,   // Monthly for 1 year
            'keep_yearly_backups_for_years' => 5,      // Yearly for 5 years

            'delete_oldest_backups_when_using_more_megabytes_than' => 10000,
        ],

        'tries' => 1,
        'retry_delay' => 0,
    ],

];
