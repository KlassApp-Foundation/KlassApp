<?php

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
        /*
         * Backup archive name — used as the filename prefix and
         * referenced by the monitor health checks.
         */
        'name' => 'klassapp',

        'source' => [
            'files' => [
                /*
                 * Directories and files to include.
                 * Only user-uploaded content and the database dump:
                 *   - storage/app/        (uploaded files, generated content)
                 *   - public/             (directly-accessible uploads)
                 * App code comes from git, not from backup archives.
                 */
                'include' => [
                    storage_path('app'),
                    public_path(),
                ],

                /*
                 * Explicit exclusions within the included paths.
                 * Also exclude .env at the project root (backed up separately, see knowledge.md).
                 */
                'exclude' => [
                    base_path('.env'),
                    base_path('vendor'),
                    base_path('node_modules'),
                    // Backup temp files are auto-excluded by the package,
                    // but listing explicitly for clarity:
                    storage_path('app/backup-temp'),
                ],

                'follow_links' => false,

                'ignore_unreadable_directories' => false,

                /*
                 * Make zip paths relative to the project root so they
                 * are portable across environments.
                 */
                'relative_path' => base_path(),
            ],

            /*
             * Database connections to dump.
             */
            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        /*
         * Compress the SQL dump with gzip before adding to the zip archive.
         */
        'database_dump_compressor' => Spatie\DbDumper\Compressors\GzipCompressor::class,

        /*
         * Timestamp format for the dump filename.
         */
        'database_dump_file_timestamp_format' => 'Y-m-d-H-i-s',

        /*
         * Destination disk(s) — backups land on S3 (Hetzner Object Storage).
         */
        'destination' => [
            'compression_method' => \ZipArchive::CM_DEFLATE,

            'compression_level' => 9,

            'filename_prefix' => '',

            'disks' => [
                's3',
            ],

            'continue_on_failure' => false,
        ],

        /*
         * Temporary working directory for building the archive.
         */
        'temporary_directory' => storage_path('app/backup-temp'),

        /*
         * Archive encryption.
         * Set BACKUP_ARCHIVE_PASSWORD in .env to enable AES-256 encryption.
         * Leave null for unencrypted (faster, but less secure).
         */
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),

        'encryption' => 'default',

        /*
         * Verify archive integrity after creation.
         */
        'verify_backup' => true,

        /*
         * Retry on transient failures.
         */
        'tries' => 3,

        'retry_delay' => 5,
    ],

    /*
     * -------------------------------------------------------------------------
     *  Notifications
     * -------------------------------------------------------------------------
     *
     * Each backup event maps to one or more notification channels.
     * Built-in: 'mail'
     * Custom:   'whatsapp'  (App\Channels\WhatsAppBackupChannel)
     *
     * Failures go to WhatsApp for immediate attention.
     * Successes are logged only (mail is optional — uncomment to enable).
     */
    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class         => ['mail', 'whatsapp'],
            UnhealthyBackupWasFoundNotification::class => ['mail', 'whatsapp'],
            CleanupHasFailedNotification::class        => ['mail', 'whatsapp'],
            // BackupWasSuccessfulNotification::class  => ['mail'],
            // HealthyBackupWasFoundNotification::class => ['mail'],
            // CleanupWasSuccessfulNotification::class => ['mail'],
        ],

        'notifiable' => \App\Notifications\Backup\BackupNotifiable::class,

        'mail' => [
            'to' => env('BACKUP_MAIL_TO', 'connect@klassapp.xyz'),

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'connect@klassapp.xyz'),
                'name'    => env('MAIL_FROM_NAME', 'KlassApp Backup'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel'     => null,
            'username'    => null,
            'icon'        => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username'    => '',
            'avatar_url'  => '',
        ],

        'webhook' => [
            'url' => '',
        ],

        /*
         * WhatsApp alert recipient.
         * Phone number in E.164 format (e.g. +256781940358).
         */
        'whatsapp' => [
            'phone' => env('BACKUP_WHATSAPP_PHONE', '+256781940358'),
        ],
    ],

    /*
     * Backup activity log channel.
     */
    'log_channel' => env('BACKUP_LOG_CHANNEL', 'daily'),

    /*
     * -------------------------------------------------------------------------
     *  Monitor (health checks)
     * -------------------------------------------------------------------------
     *
     * The monitor runs via `backup:monitor` and verifies that the most recent
     * backup is not older than 1 day and the total storage used is within limits.
     */
    'monitor_backups' => [
        [
            'name'  => 'klassapp',
            'disks' => ['s3'],
            'health_checks' => [
                MaximumAgeInDays::class           => 1,
                MaximumStorageInMegabytes::class  => 5000,
            ],
        ],
    ],

    /*
     * -------------------------------------------------------------------------
     *  Cleanup (retention policy)
     * -------------------------------------------------------------------------
     *
     * The default strategy keeps:
     *   - All backups for 7 days
     *   - Daily backups for 30 days
     *   - Weekly backups for 8 weeks
     *   - Monthly backups for 4 months
     *   - Yearly backups for 2 years
     *   - Unlimited total storage (delete_oldest… set to null)
     */
    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days'               => 7,
            'keep_daily_backups_for_days'             => 30,
            'keep_weekly_backups_for_weeks'           => 8,
            'keep_monthly_backups_for_months'         => 4,
            'keep_yearly_backups_for_years'           => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => null,
        ],

        'tries'       => 3,
        'retry_delay' => 5,
    ],
];
