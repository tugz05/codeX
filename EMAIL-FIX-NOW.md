# 🚨 Email Not Arriving - Immediate Fix

## Current Status
- ✅ Template is fine
- ✅ Notifications are queuing
- ✅ Jobs are processing  
- ❌ **Emails NOT arriving**

## Problem
Most likely: **SMTP settings or connection issue**

---

## 🎯 IMMEDIATE ACTION:

### 1. Upload This File to Server:
```
app/Console/Commands/TestMailCommand.php
```

### 2. Run Direct Mail Test:
```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html
php artisan mail:test kcsilagan@nemsu.edu.ph
```

### 3. Check Output:

**If you see:**
```
✓ Email sent successfully!
```
→ Check inbox (wait 1-2 min), if email arrives, SMTP works!

**If you see:**
```
✗ Failed to send email!
Error: ...
```
→ This is the problem! Send me the full error.

---

## 🔧 Quick Fix #1: Try Different Port

Current settings use port 465. Try 587 instead:

```bash
# Edit .env on server
nano .env

# Change these lines:
MAIL_PORT=587
MAIL_ENCRYPTION=tls

# (Keep everything else the same)

# Save and exit (Ctrl+X, Y, Enter)

# Clear config
php artisan config:clear
php artisan config:cache

# Test again
php artisan mail:test kcsilagan@nemsu.edu.ph
```

---

## 🔧 Quick Fix #2: Verify Email Settings

Check these in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465  # or try 587
MAIL_USERNAME=admin@nemsu-codex.online  # Must match FROM address
MAIL_PASSWORD=Tugz@0508  # Correct password?
MAIL_ENCRYPTION=ssl  # or try tls with port 587
MAIL_FROM_ADDRESS=admin@nemsu-codex.online  # Must exist in Hostinger
```

---

## 📧 Check Email Account

In **Hostinger hPanel → Email**:
1. Verify `admin@nemsu-codex.online` exists
2. Check password is correct
3. Check for any email errors/blocks
4. Look at email logs if available

---

## 🆘 If Direct Mail Test Fails:

Send me:
1. **Full error message** from `php artisan mail:test`
2. **Your mail settings** (hide password):
   ```bash
   php artisan tinker
   >>> config('mail.mailers.smtp')
   >>> exit
   ```
3. **Does email account exist in Hostinger?**

---

## ✅ If Direct Mail Test Works:

Then problem is queue processing. Run:
```bash
php artisan queue:work database --once --queue=notifications --verbose
```

And send me the output.

---

**UPLOAD TestMailCommand.php and RUN THE TEST NOW!** This will show us exactly what's wrong. 🚀
