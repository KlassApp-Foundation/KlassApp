<?php
// Bootstrap Laravel, dump key env state.
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "APP_ENV: " . env('APP_ENV') . PHP_EOL;
echo "APP_DEBUG: " . (env('APP_DEBUG') ? 'TRUE' : 'FALSE') . PHP_EOL;
echo "LOG_CHANNEL: " . env('LOG_CHANNEL') . PHP_EOL;
echo "logging.default: " . config('logging.default') . PHP_EOL;
echo "logging.channels.stack.channels: " . json_encode(config('logging.channels.stack.channels')) . PHP_EOL;
echo "logging.channels.single.path: " . config('logging.channels.single.path') . PHP_EOL;
echo "logging.channels.daily.path: " . config('logging.channels.daily.path') . PHP_EOL;
echo "logging.channels.daily.days: " . config('logging.channels.daily.days') . PHP_EOL;
echo "logging.channels.errorlog: " . json_encode(config('logging.channels.errorlog')) . PHP_EOL;
echo "LOG_SLACK_WEBHOOK_URL: " . (env('LOG_SLACK_WEBHOOK_URL') ? 'SET' : 'NULL') . PHP_EOL;
echo "MAIL_MAILER: " . env('MAIL_MAILER') . PHP_EOL;
echo "MAIL_FROM_ADDRESS: " . (env('MAIL_FROM_ADDRESS') ?: 'NULL') . PHP_EOL;
echo "QUEUE_CONNECTION: " . env('QUEUE_CONNECTION') . PHP_EOL;
