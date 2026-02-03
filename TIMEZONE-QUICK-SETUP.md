# ⚡ Timezone Setup - Quick Reference

## ⏰ **All System Times: UTC+08:00 (Philippine Time)**

---

## ✅ **What Was Configured:**

1. **Laravel Config** (`config/app.php`):
   ```php
   'timezone' => 'Asia/Manila',
   ```

2. **Environment** (`.env`):
   ```env
   APP_TIMEZONE=Asia/Manila
   ```

3. **Cron Script** (`queue-cron.php`):
   ```php
   date_default_timezone_set('Asia/Manila');
   ```

4. **Assignment Status Command**:
   ```php
   Carbon::now('Asia/Manila')
   ```

---

## 🧪 **Test Locally:**

```bash
php artisan config:clear
php artisan tinker
```

```php
>>> config('app.timezone')
=> "Asia/Manila"

>>> Carbon::now()
=> Carbon @... { date: 2026-02-03 17:30:00.0 Asia/Manila (+08:00) }

>>> Carbon::now()->format('P')
=> "+08:00"  ← Should show +08:00
```

---

## 🚀 **Deploy to Hostinger:**

### **1. Upload Files:**
- `config/app.php` (updated)
- `queue-cron.php` (updated)
- `app/Console/Commands/UpdateAssignmentStatusCommand.php` (updated)

### **2. Update `.env` on Server:**
```bash
nano .env
```

Add this line after `APP_DEBUG`:
```env
APP_TIMEZONE=Asia/Manila
```

### **3. Clear Config:**
```bash
php artisan config:clear
php artisan config:cache
```

### **4. Test:**
```bash
php artisan tinker
>>> config('app.timezone')
>>> Carbon::now()
```

### **5. Verify Cron Logs:**
```bash
tail -10 storage/logs/cron.log
```

**Should show:**
```
[2026-02-03 17:30:00] Queue processed  ← Philippine Time (not UTC!)
```

---

## 📊 **What This Affects:**

- ✅ Assignment deadlines
- ✅ Submission timestamps
- ✅ Missing/late detection
- ✅ Email timestamps
- ✅ Message times
- ✅ Attendance records
- ✅ All logs

---

## ⚠️ **Important:**

**Always use Carbon for dates:**
```php
// ✅ GOOD
Carbon::now()
Carbon::parse($date)

// ❌ AVOID
date('Y-m-d H:i:s')
time()
```

---

## ✅ **Success Check:**

```bash
php artisan tinker
>>> Carbon::now()->format('Y-m-d H:i:s P')
```

**Expected:**
```
"2026-02-03 17:30:00 +08:00"
                      ^^^^^^
                      Should be +08:00
```

---

**Done! All times now use Philippine Time (UTC+08:00)** 🇵🇭
