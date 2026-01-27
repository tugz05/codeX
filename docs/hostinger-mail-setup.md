# Hostinger Mail Setup

Use Hostinger SMTP credentials in your production `.env` so email notifications send correctly.

## Required `.env` values

```
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=465
MAIL_USERNAME=yourname@yourdomain.com
MAIL_PASSWORD=your-mailbox-password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=yourname@yourdomain.com
MAIL_FROM_NAME="CodeX"
```

### Port / Encryption
Hostinger commonly supports:
- `465` with `MAIL_ENCRYPTION=ssl`
- `587` with `MAIL_ENCRYPTION=tls`

## After updating `.env`

```
php artisan config:clear
php artisan cache:clear
```

## Test email (optional)

```
php artisan tinker
>>> \Illuminate\Support\Facades\Mail::raw('Test', fn($m) => $m->to('you@example.com')->subject('Mail test'));
```

## Notes
- Notifications are already wired to send email when enabled in user preferences.
- Ensure the user has email notifications enabled in notification preferences.
