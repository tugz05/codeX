# ⚡ QUICK SETUP - Queue Cron Job

## 📦 **Upload This File:**
```
queue-cron.php
```

**To:**
```
/home/u775863429/domains/nemsu-codex.online/public_html/queue-cron.php
```

---

## ✅ **Test It Works:**

SSH into Hostinger and run:
```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html
php queue-cron.php
```

**Expected:** Should see "Queue worker completed successfully!"

---

## ⚙️ **Create Cron Job:**

### In Hostinger → Advanced → Cron Jobs:

**Command:**
```
/usr/bin/php /home/u775863429/domains/nemsu-codex.online/public_html/queue-cron.php
```

**Schedule:** `* * * * *` (every minute)

---

## 🧪 **Verify:**

### 1. Check logs (wait 2 minutes after creating cron):
```bash
tail -20 storage/logs/cron.log
```

### 2. Create material via web

### 3. Wait 1-2 minutes

### 4. Check email inbox: `kcsilagan@nemsu.edu.ph`

**✅ Email should arrive!**

---

## 🎯 **What It Does:**

1. Processes **notifications** queue → Sends emails 📧
2. Processes **default** queue → Processes messages 💬
3. Runs every minute automatically ⏰

---

## 🔍 **Troubleshooting:**

**Cron not running?**
- Check cron is enabled in Hostinger
- View output by clicking "View Output" button

**No emails?**
- Check: `tail -50 storage/logs/laravel.log`
- Look for: "Email notification queued successfully"
- Run manually: `php queue-cron.php`

**Permission errors?**
```bash
chmod 644 queue-cron.php
chmod -R 775 storage
```

---

**That's it! Upload, create cron, done!** 🚀
