# ⏰ Timezone Configuration - UTC+08:00

## 🌏 **Timezone Setting:**

The entire system uses **Asia/Manila (UTC+08:00)** - Philippine Time

---

## ✅ **What Was Configured:**

### **1. Laravel Application Timezone**
**File:** `config/app.php`
```php
'timezone' => 'Asia/Manila',
```

This ensures all Laravel date/time functions use Philippine Time.

### **2. Queue Cron Script**
**File:** `queue-cron.php`
```php
date_default_timezone_set('Asia/Manila');
```

Ensures cron job logs and timestamps use Philippine Time.

### **3. Assignment Status Command**
**File:** `app/Console/Commands/UpdateAssignmentStatusCommand.php`
```php
$now = Carbon::now('Asia/Manila');
$dueDate = Carbon::parse($assignment->due_date, 'Asia/Manila');
```

Ensures assignment deadlines are checked in Philippine Time.

### **4. Environment Configuration**
**File:** `.env`
```env
APP_TIMEZONE=Asia/Manila
```

---

## 📅 **What This Means:**

### **All timestamps will show in Philippine Time:**
- ✅ Assignment due dates/times
- ✅ Submission timestamps
- ✅ Email notification times
- ✅ Message timestamps
- ✅ Attendance records
- ✅ Log files
- ✅ Database records (via Carbon)

### **Examples:**

**Before (UTC):**
```
Due Date: 2026-02-03 16:00:00  (UTC)
```

**After (Philippine Time):**
```
Due Date: 2026-02-04 00:00:00  (Asia/Manila / UTC+08:00)
```

---

## 🔄 **How Carbon Works:**

Laravel's Carbon library automatically uses the application timezone:

```php
// These all use Asia/Manila timezone
Carbon::now();                    // Current time in Philippine Time
Carbon::parse('2026-02-04');      // Parse in Philippine Time
Carbon::tomorrow();                // Tomorrow in Philippine Time
```

---

## 🗄️ **Database Considerations:**

### **Important Notes:**

1. **Database timestamps are still stored in UTC** (recommended practice)
2. **Laravel automatically converts to/from app timezone**
3. **When querying, Laravel handles conversion automatically**

### **How it works:**
```php
// User creates assignment due "Feb 4, 2026 11:59 PM" (Philippine Time)
// Laravel stores as "Feb 4, 2026 3:59 PM" (UTC) in database
// When retrieved, shows as "Feb 4, 2026 11:59 PM" (Philippine Time)
```

This is **correct behavior** - it allows:
- Users in different timezones to see correct local time
- Server location to not affect displayed times
- Consistent storage format

---

## 🧪 **Testing Timezone:**

### **Test Current Timezone:**

```bash
php artisan tinker
```

```php
// Check application timezone
>>> config('app.timezone')
=> "Asia/Manila"

// Check current time
>>> \Carbon\Carbon::now()
=> Carbon\Carbon @1738569600 {
     date: 2026-02-03 17:30:00.0 Asia/Manila (+08:00)
   }

// Check timezone offset
>>> \Carbon\Carbon::now()->format('Y-m-d H:i:s P')
=> "2026-02-03 17:30:00 +08:00"

// Verify it's 8 hours ahead of UTC
>>> \Carbon\Carbon::now('UTC')
=> Carbon\Carbon @1738569600 {
     date: 2026-02-03 09:30:00.0 UTC (+00:00)
   }
```

### **Expected Results:**
- Application timezone: `Asia/Manila`
- Time offset: `+08:00`
- Time should be 8 hours ahead of UTC

---

## 📝 **Verify After Deployment:**

### **1. Check Config Cache:**
```bash
php artisan config:clear
php artisan config:cache
```

### **2. Test Timezone:**
```bash
php artisan tinker
>>> config('app.timezone')
>>> \Carbon\Carbon::now()
```

### **3. Check Cron Logs:**
```bash
tail -10 storage/logs/cron.log
```

**Should show Philippine Time:**
```
[2026-02-03 17:30:00] Queue processed  ← Philippine Time
```

### **4. Create Test Assignment:**
- Set due date/time
- Check database: `due_date` column
- Verify time displays correctly in UI

---

## 🌐 **Timezone Reference:**

| Timezone Name | Offset | Also Known As |
|---------------|--------|---------------|
| Asia/Manila | UTC+08:00 | Philippine Time (PHT) |
| Asia/Singapore | UTC+08:00 | Singapore Time (SGT) |
| Asia/Hong_Kong | UTC+08:00 | Hong Kong Time (HKT) |
| Asia/Shanghai | UTC+08:00 | China Standard Time (CST) |

**Note:** All these are UTC+08:00 (same offset)

---

## ⚠️ **Important Reminders:**

### **1. Always Use Carbon for Date/Time Operations:**
```php
// ✅ GOOD - Uses app timezone
Carbon::now()
Carbon::parse($date)
Carbon::createFromFormat('Y-m-d H:i:s', $datetime)

// ❌ AVOID - Uses server timezone (may differ)
date('Y-m-d H:i:s')
time()
new DateTime()
```

### **2. For Database Queries with Dates:**
```php
// ✅ GOOD - Carbon handles timezone
$assignments = Assignment::where('due_date', '>=', Carbon::now())->get();

// ✅ GOOD - Specify timezone
$date = Carbon::parse($input, 'Asia/Manila');

// ❌ AVOID - Raw SQL dates without timezone consideration
DB::raw("DATE(due_date) = '2026-02-04'")
```

### **3. Frontend Date Display:**
```javascript
// Make sure frontend also uses Philippine Time
const date = new Date(submission.created_at);
const options = { timeZone: 'Asia/Manila' };
const displayDate = date.toLocaleString('en-PH', options);
```

---

## 🔧 **Configuration Files Updated:**

- ✅ `config/app.php` - Application timezone
- ✅ `queue-cron.php` - Cron script timezone
- ✅ `app/Console/Commands/UpdateAssignmentStatusCommand.php` - Command timezone
- ✅ `.env.example` - Environment variable documentation

---

## 📊 **Affected Features:**

| Feature | Timezone Impact |
|---------|-----------------|
| **Assignment Deadlines** | ✅ Checked in Philippine Time |
| **Submission Times** | ✅ Recorded in Philippine Time |
| **Missing Status** | ✅ Determined using Philippine Time |
| **Late Detection** | ✅ Calculated with Philippine Time |
| **Email Timestamps** | ✅ Show Philippine Time |
| **Attendance Records** | ✅ Use Philippine Time |
| **Message Times** | ✅ Display Philippine Time |
| **Cron Logs** | ✅ Written in Philippine Time |

---

## ✅ **Deployment Checklist:**

### **Local:**
- [x] Update `config/app.php`
- [x] Update `queue-cron.php`
- [x] Update `UpdateAssignmentStatusCommand.php`
- [x] Update `.env.example`
- [x] Clear config cache: `php artisan config:clear`
- [x] Test: `php artisan tinker` → `Carbon::now()`

### **Hostinger:**
- [ ] Upload updated files
- [ ] Clear config cache: `php artisan config:cache`
- [ ] Test timezone in tinker
- [ ] Verify cron log timestamps
- [ ] Create test assignment and check due date
- [ ] Monitor for any timezone-related issues

---

## 🎯 **Quick Verification:**

```bash
# Check application timezone
php artisan tinker
>>> config('app.timezone')
=> "Asia/Manila"

# Check current time
>>> Carbon::now()
=> Carbon @... { date: 2026-02-03 17:30:00.0 Asia/Manila (+08:00) }

# Check offset
>>> Carbon::now()->format('P')
=> "+08:00"
```

**Expected:** All results show `Asia/Manila` and `+08:00`

---

**The entire system now uses UTC+08:00 (Philippine Time)!** 🇵🇭
