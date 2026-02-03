# 🔍 Complete Email Testing Guide

## Problem
- Notifications are being queued ✅
- Jobs are being processed ✅
- BUT emails are NOT arriving ❌

## Possible Causes
1. SMTP connection failing
2. Mail being blocked/rejected by server
3. Jobs processing but mail send failing silently
4. Email going to spam
5. Mail configuration issue

---

## 🧪 Test 1: Direct Mail Send (Bypass Queue)

This tests if SMTP settings work at all:

```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html

# Upload the new TestMailCommand.php first!
# Then run:
php artisan mail:test kcsilagan@nemsu.edu.ph
```

**This command will:**
1. Show your mail configuration
2. Send email DIRECTLY (not queued)
3. Test if SMTP connection works

**Expected Results:**

### ✅ If SMTP Works:
```
✓ Email sent successfully!
Check inbox: kcsilagan@nemsu.edu.ph
```
→ **Email should arrive within 1-2 minutes**  
→ **Problem is with queue processing, not SMTP**

### ❌ If SMTP Fails:
```
✗ Failed to send email!
Error: Connection timeout / Authentication failed / etc.
```
→ **Problem is SMTP configuration**  
→ **Need to fix mail settings**

---

## 🧪 Test 2: Check Failed Jobs

```bash
php artisan queue:failed
```

**If you see failed jobs:**
- Read the error message
- Likely shows mail connection error
- Fix the error and retry: `php artisan queue:retry all`

**If no failed jobs:**
- Jobs are "succeeding" but mail isn't sending
- Possible silent failure in mail system

---

## 🧪 Test 3: Process Job with Verbose Output

```bash
php artisan queue:work database --once --queue=notifications --verbose
```

**Watch for:**
- `Processing: Illuminate\Notifications\SendQueuedNotifications`
- Any errors during processing
- `Processed` means it thinks it succeeded

---

## 🧪 Test 4: Check Laravel Log for Mail Errors

```bash
tail -100 storage/logs/laravel.log | grep -i "mail\|smtp\|failed"
```

**Look for:**
- `Connection timeout`
- `Authentication failed`
- `Could not connect to SMTP host`
- `Failed to send`

---

## 🔧 Common SMTP Issues & Fixes

### Issue 1: Port 465 with wrong encryption

**Current settings:**
```env
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
```

**Try changing to:**
```env
MAIL_PORT=587
MAIL_ENCRYPTION=tls
```

Then clear config:
```bash
php artisan config:clear
php artisan config:cache
```

### Issue 2: Hostinger requires specific settings

**Verify these settings:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=admin@nemsu-codex.online
MAIL_PASSWORD=your-actual-password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=admin@nemsu-codex.online
MAIL_FROM_NAME="codeX-NEMSU"
```

### Issue 3: Email password incorrect

- Verify password is correct
- Check if email account exists in Hostinger
- Try resetting email password

### Issue 4: Hostinger blocking outgoing mail

- Check Hostinger email logs
- Contact Hostinger support
- Verify email account has sending permissions

---

## 🎯 Action Plan

### Step 1: Upload TestMailCommand.php

Upload this file to your server:
```
app/Console/Commands/TestMailCommand.php
```

### Step 2: Run Direct Mail Test

```bash
php artisan mail:test kcsilagan@nemsu.edu.ph
```

### Step 3: Check Results

**A) If direct mail works:**
- Check inbox for "Test Email (Direct)"
- If arrives → SMTP is fine, problem is queue processing
- Run: `php artisan queue:work database --once --queue=notifications`
- Check inbox for "Test Assignment" email

**B) If direct mail fails:**
- Read error message carefully
- Common errors:
  - `Connection timeout` → Wrong host/port
  - `Authentication failed` → Wrong username/password
  - `535 5.7.8` → Invalid credentials
- Fix mail settings in `.env`
- Run `php artisan config:clear && php artisan config:cache`
- Try again

### Step 4: Check for Jobs in Queue

```bash
# Create test assignment via web
# Then immediately check:
tail -20 storage/logs/laravel.log | grep "Email notification queued"
php artisan queue:monitor database
```

**Should see:**
- Log: "Email notification queued successfully"
- Queue: 1 or more jobs

### Step 5: Process and Check Failed

```bash
php artisan queue:work database --once --queue=notifications --verbose
php artisan queue:failed
```

**If job fails:**
- Copy the full error message
- Send to me for analysis

---

## 📊 Diagnostic Checklist

Run these and send me ALL output:

```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html

echo "=== 1. Test direct mail send ==="
php artisan mail:test kcsilagan@nemsu.edu.ph

echo ""
echo "=== 2. Check failed jobs ==="
php artisan queue:failed

echo ""
echo "=== 3. Check mail errors in log ==="
tail -100 storage/logs/laravel.log | grep -i "mail\|smtp"

echo ""
echo "=== 4. Verify mail config ==="
php artisan tinker
>>> config('mail.mailers.smtp')
>>> exit
```

---

## 🆘 Quick Fixes

### Fix 1: Try Port 587 Instead

```bash
# Edit .env
nano .env

# Change:
MAIL_PORT=587
MAIL_ENCRYPTION=tls

# Clear cache
php artisan config:clear
php artisan config:cache

# Test
php artisan mail:test kcsilagan@nemsu.edu.ph
```

### Fix 2: Test with Gmail (if Hostinger mail fails)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-gmail@gmail.com
```

(Requires Gmail App Password, not regular password)

---

## ✅ Success Indicators

Mail system working when:
- ✅ `php artisan mail:test` sends email successfully
- ✅ Email arrives in inbox within 1-2 minutes
- ✅ Queue test also sends email
- ✅ Creating real assignment sends email
- ✅ No failed jobs
- ✅ No mail errors in logs

---

**Upload TestMailCommand.php and run the test!** This will tell us exactly what's wrong. 🚀
