# Hostinger Queue Worker Setup (Cron Jobs for Email/Notifications)

This guide explains how to set up automated email and notification processing using Hostinger's cron jobs.

## Overview

The application uses Laravel's queue system to send emails and notifications asynchronously. Instead of sending emails immediately (which can slow down the application), jobs are added to a queue and processed in the background by a worker.

## Prerequisites

1. SSH access to your Hostinger account (or use Hostinger's cron job interface)
2. Laravel application deployed to Hostinger
3. Database queue tables created (via migrations)

## Step 1: Verify Queue Configuration

Ensure your `.env` file on Hostinger has the following settings:

```env
QUEUE_CONNECTION=database
DB_QUEUE_TABLE=jobs
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=90

# Mail settings (use your Hostinger mail credentials)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Step 2: Make Queue Worker Script Executable

If using SSH, make the queue worker script executable:

```bash
cd /home/your-username/public_html
chmod +x queue-worker.sh
```

## Step 3: Set Up Cron Job in Hostinger

### Option A: Using Hostinger Control Panel (Recommended)

1. Log in to your Hostinger control panel
2. Go to **Advanced** → **Cron Jobs**
3. Click **Create Cron Job**
4. Configure the cron job:

   **For Every Minute (Recommended for real-time processing):**
   ```
   Command Type: Custom
   Minute: * (every minute)
   Hour: *
   Day: *
   Month: *
   Weekday: *
   Command: cd /home/YOUR_USERNAME/domains/YOUR_DOMAIN/public_html && /usr/bin/php artisan queue:work database --once --queue=notifications,default --tries=3 --timeout=90 > /dev/null 2>&1
   ```

   **For Every 5 Minutes (Lower server load):**
   ```
   Command Type: Custom
   Minute: */5
   Hour: *
   Day: *
   Month: *
   Weekday: *
   Command: cd /home/YOUR_USERNAME/domains/YOUR_DOMAIN/public_html && /usr/bin/php artisan queue:work database --stop-when-empty --queue=notifications,default --tries=3 --timeout=90 > /dev/null 2>&1
   ```

   **Replace:**
   - `YOUR_USERNAME` with your Hostinger username (e.g., `u775863429`)
   - `YOUR_DOMAIN` with your domain name (e.g., `nemsu-codex.online`)

5. Click **Create** to save the cron job

### Option B: Using Shell Script

If you prefer using the shell script:

```
Command: cd /home/YOUR_USERNAME/domains/YOUR_DOMAIN/public_html && ./queue-worker.sh > /dev/null 2>&1
```

### Option C: Using cPanel (if available)

1. Access cPanel
2. Navigate to **Advanced** → **Cron Jobs**
3. Add new cron job with the command from Option A

## Step 4: Verify Queue Worker is Running

### Check if jobs are being processed:

```bash
# View jobs table
php artisan queue:monitor database

# Check for failed jobs
php artisan queue:failed
```

### Monitor queue in real-time (via SSH):

```bash
# Watch the jobs table
watch -n 2 'php artisan queue:monitor database'
```

## Step 5: Test the Queue

1. Trigger an action that sends an email (e.g., add a student to a class)
2. Check the `jobs` table in your database - you should see pending jobs
3. Wait for the cron job to run (1-5 minutes depending on your schedule)
4. Check if the email was sent and the job was removed from the table

## Troubleshooting

### Jobs are stuck in the queue

**Check the cron job output:**
Temporarily change the cron command to log output:
```bash
cd /home/YOUR_USERNAME/domains/YOUR_DOMAIN/public_html && /usr/bin/php artisan queue:work database --once --queue=notifications,default --tries=3 --timeout=90 >> storage/logs/queue.log 2>&1
```

**Check failed jobs:**
```bash
php artisan queue:failed
```

**Retry failed jobs:**
```bash
php artisan queue:retry all
```

**Clear stuck jobs:**
```bash
php artisan queue:clear database
```

### Emails not sending

1. Verify mail configuration in `.env`
2. Test mail settings:
   ```bash
   php artisan tinker
   Mail::raw('Test email', fn($msg) => $msg->to('test@example.com')->subject('Test'));
   ```
3. Check Hostinger email logs in control panel

### Cron job not executing

1. Verify the PHP path - Hostinger usually uses `/usr/bin/php`
2. Check cron job syntax and paths are correct
3. Ensure file permissions are correct (644 for PHP files)
4. Check Hostinger cron job execution history in control panel

## Performance Optimization

### For high-traffic applications:

**Process more jobs per execution:**
```bash
# Process up to 10 jobs per cron execution
/usr/bin/php artisan queue:work database --max-jobs=10 --queue=notifications,default --tries=3 --timeout=90
```

**Run multiple cron jobs:**
Create separate cron jobs for different queues:
- Every minute for `notifications` queue (critical)
- Every 5 minutes for `default` queue (non-critical)

## Queue Commands Reference

```bash
# Process one job
php artisan queue:work database --once

# Process jobs until queue is empty
php artisan queue:work database --stop-when-empty

# Process jobs for specific time
php artisan queue:work database --max-time=60

# List all queued jobs
php artisan queue:monitor database

# View failed jobs
php artisan queue:failed

# Retry specific failed job
php artisan queue:retry {job-id}

# Retry all failed jobs
php artisan queue:retry all

# Delete failed job
php artisan queue:forget {job-id}

# Clear all jobs from queue
php artisan queue:clear database

# Restart all queue workers (for code updates)
php artisan queue:restart
```

## Best Practices

1. **Start with conservative timing** - Use `*/5` (every 5 minutes) initially, then increase frequency if needed
2. **Monitor performance** - Check server load and adjust cron frequency accordingly
3. **Set up alerts** - Monitor failed jobs and set up notifications
4. **Regular maintenance** - Clear old failed jobs periodically
5. **Use specific queues** - Separate critical (notifications) from non-critical jobs
6. **Timeout management** - Set appropriate `--timeout` values based on your longest job

## Important Notes for Hostinger

- Shared hosting has resource limits - don't run too many concurrent queue workers
- Use `--once` or `--stop-when-empty` instead of continuous `queue:work` to avoid process buildup
- Monitor your resource usage in Hostinger control panel
- Consider upgrading to VPS if you need more intensive queue processing

## Security Considerations

- Keep `.env` file secure and outside public_html if possible
- Don't log sensitive information in queue jobs
- Regularly rotate mail credentials
- Monitor for unauthorized access to queue system

## Support

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check queue logs (if configured)
3. Review Hostinger cron job execution history
4. Contact Hostinger support for server-related issues
