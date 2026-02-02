# 📧 Queue System Setup (Email & Notifications)

This application uses Laravel's queue system to send emails and notifications asynchronously, ensuring fast response times and better user experience.

## 🚀 Quick Start for Hostinger

**See:** [docs/CRON-SETUP-QUICK-REFERENCE.md](docs/CRON-SETUP-QUICK-REFERENCE.md)

### One-Line Setup

1. **Go to Hostinger hPanel** → Advanced → Cron Jobs
2. **Add this cron job** (runs every minute):

```bash
cd /home/YOUR_USERNAME/domains/YOUR_DOMAIN/public_html && /usr/bin/php artisan queue:work database --once --queue=notifications,default --tries=3 --timeout=90 > /dev/null 2>&1
```

3. **Replace:**
   - `YOUR_USERNAME` → Your Hostinger username (e.g., `u775863429`)
   - `YOUR_DOMAIN` → Your domain (e.g., `nemsu-codex.online`)

4. **Done!** Emails will now be sent automatically.

## 📚 Documentation

- **Quick Reference:** [docs/CRON-SETUP-QUICK-REFERENCE.md](docs/CRON-SETUP-QUICK-REFERENCE.md) - 5-minute setup guide
- **Full Documentation:** [docs/hostinger-queue-setup.md](docs/hostinger-queue-setup.md) - Complete guide with troubleshooting

## 🧪 Testing

### Test the queue system:

```bash
# Send a test email
php artisan queue:test your-email@example.com

# Process the job manually
php artisan queue:work database --once

# Check queue status
php artisan queue:monitor database

# View failed jobs
php artisan queue:failed
```

## ⚙️ Configuration

Ensure your `.env` file has:

```env
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

## 🔧 Troubleshooting

### Emails not sending?

```bash
# Check for failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Clear stuck jobs
php artisan queue:clear database
```

### Check cron job is running

1. Go to Hostinger hPanel → Cron Jobs
2. Check "Last Execution" timestamp
3. View execution log if available

## 📊 How It Works

1. **User Action** → Creates a notification (e.g., student added to class)
2. **Queue Job** → Notification is added to `jobs` table in database
3. **Cron Worker** → Runs every minute, processes pending jobs
4. **Email Sent** → Job removed from queue when complete

## 🎯 What Uses the Queue

- ✉️ Class invitation emails
- 📬 Grade release notifications
- 📝 Assignment/quiz creation notifications
- 💬 Message notifications
- 📢 Announcements
- ⏰ Deadline reminders

## 🆘 Support

- Check logs: `storage/logs/laravel.log`
- Monitor queue: `php artisan queue:monitor database`
- Test email: `php artisan queue:test your-email@example.com`
- Full docs: [docs/hostinger-queue-setup.md](docs/hostinger-queue-setup.md)

---

**Status:** ✅ Configured for Hostinger shared hosting  
**Last Updated:** January 2026  
**Compatible:** Laravel 11+
