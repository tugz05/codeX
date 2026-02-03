<?php

/**
 * Queue Worker Cron Script
 *
 * This script processes queued jobs (email notifications, etc.)
 * Designed to be run via cron job on shared hosting
 *
 * Usage: php /path/to/queue-cron.php
 */

// Ensure we're running from CLI
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

// Start timing
$startTime = microtime(true);
$timestamp = date('Y-m-d H:i:s');

// Change to Laravel root directory (where this script is located)
chdir(__DIR__);

// Load Laravel
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Boot Laravel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "[{$timestamp}] Starting queue worker...\n";

    // Process jobs from notifications queue first (emails!)
    echo "Processing notifications queue...\n";
    $exitCode1 = \Illuminate\Support\Facades\Artisan::call('queue:work', [
        'connection' => 'database',
        '--once' => true,
        '--queue' => 'notifications',
        '--tries' => 3,
        '--timeout' => 90,
    ]);

    $output1 = \Illuminate\Support\Facades\Artisan::output();
    echo $output1;

    // Process jobs from default queue (messages, etc.)
    echo "Processing default queue...\n";
    $exitCode2 = \Illuminate\Support\Facades\Artisan::call('queue:work', [
        'connection' => 'database',
        '--once' => true,
        '--queue' => 'default',
        '--tries' => 3,
        '--timeout' => 90,
    ]);

    $output2 = \Illuminate\Support\Facades\Artisan::output();
    echo $output2;

    // Calculate execution time
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);

    // Log to file
    $logFile = __DIR__ . '/storage/logs/cron.log';
    $logEntry = "[{$timestamp}] Queue processed\n";
    $logEntry .= "  Notifications queue: exit code {$exitCode1}\n";
    $logEntry .= "  Default queue: exit code {$exitCode2}\n";
    $logEntry .= "  Execution time: {$executionTime}ms\n\n";

    @file_put_contents($logFile, $logEntry, FILE_APPEND);

    echo "\n✓ Queue worker completed successfully!\n";
    echo "Execution time: {$executionTime}ms\n";

    exit(0);

} catch (\Exception $e) {
    $error = "[{$timestamp}] ERROR: {$e->getMessage()}\n";
    $error .= $e->getTraceAsString() . "\n\n";

    // Log error
    $logFile = __DIR__ . '/storage/logs/cron.log';
    @file_put_contents($logFile, $error, FILE_APPEND);

    echo "✗ Error: {$e->getMessage()}\n";
    exit(1);
}
