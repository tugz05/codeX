#!/bin/bash

# Queue Worker Script for Hostinger Cron Jobs
# This script processes queued jobs (emails, notifications, etc.)

# Change to the Laravel project directory
cd "$(dirname "$0")" || exit

# Process queue jobs
# Using --once to process a single job per cron execution (safer for shared hosting)
# Using --queue to prioritize critical queues
php artisan queue:work database --once --queue=notifications,default --tries=3 --timeout=90 2>&1

# Alternative: Use --stop-when-empty to process all pending jobs then stop
# php artisan queue:work database --stop-when-empty --queue=notifications,default --tries=3 --timeout=90 2>&1
