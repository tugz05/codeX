# 🔍 Notification Debugging - Resource Creation Fix

## ✅ What I Fixed

### Problem:
- `php artisan queue:test` works ✅
- BUT creating assignments/activities/etc. doesn't send emails ❌

### Root Cause:
Possible issues:
1. Student query using wrong column name (`status` vs `classlist_user.status`)
2. No students in the class
3. User preferences blocking emails
4. Notification class not found
5. Silent errors

### Solution Applied:

**1. Fixed Student Query:**
Changed from:
```php
$students = $targetClasslist->students()->where('status', 'active')->get();
```

To:
```php
$students = $targetClasslist->students()->where('classlist_user.status', 'active')->get();
```

**2. Added Debug Logging:**
- Now logs when attempting to send notifications
- Logs student count
- Logs if user preferences block emails
- Logs if notification class doesn't exist
- Logs all errors with full stack trace

**3. Files Updated:**
- `app/Services/NotificationService.php` - Added comprehensive logging
- `app/Http/Controllers/AssignmentController.php` - Fixed query + logging
- `app/Http/Controllers/QuizController.php` - Fixed query + logging
- `app/Http/Controllers/ActivityController.php` - Fixed query + logging
- `app/Http/Controllers/ExaminationController.php` - Fixed query + logging
- `app/Http/Controllers/MaterialController.php` - Fixed query + logging

---

## 🚀 Deploy & Test

### Step 1: Upload to Hostinger

Upload these files:
```
app/Services/NotificationService.php
app/Http/Controllers/AssignmentController.php
app/Http/Controllers/QuizController.php
app/Http/Controllers/ActivityController.php
app/Http/Controllers/ExaminationController.php
app/Http/Controllers/MaterialController.php
```

### Step 2: Clear Cache

```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Step 3: Create a Test Assignment

Via web interface:
1. Log in as instructor
2. Go to a class
3. Create a new assignment
4. Add some text
5. Click "Create"

### Step 4: Check Logs IMMEDIATELY

```bash
tail -100 storage/logs/laravel.log
```

**Look for these log entries:**

**✅ Good Signs:**
```
Sending assignment notifications
Attempting to send email notification
Email notification queued successfully
students_count: 5  (or however many students you have)
```

**❌ Bad Signs:**
```
students_count: 0  (No students!)
Email notification skipped - user preference disabled
Notification class does not exist
Failed to queue email notification
```

### Step 5: Check Queue

```bash
php artisan queue:monitor database
```

**Should show:**
- Pending jobs: 5 (or number of students)

**If 0 jobs:**
- Check the log output from Step 4
- Something is preventing jobs from being created

### Step 6: Wait or Process Manually

**Option A: Wait for cron (1-5 minutes)**

**Option B: Process manually:**
```bash
php artisan queue:work database --stop-when-empty
```

### Step 7: Check Email

Check student inboxes - should receive assignment notification email.

---

## 🔍 Debugging Scenarios

### Scenario 1: Log shows "students_count: 0"

**Problem:** No students in the class, or student status is not 'active'

**Solution:**
```bash
# Check students in the class
php artisan tinker
>>> $classlist = App\Models\Classlist::find(YOUR_CLASS_ID);
>>> $students = $classlist->students()->where('classlist_user.status', 'active')->get();
>>> $students->count()
>>> $students->pluck('email')
>>> exit
```

If count is 0:
- Add students to the class
- OR check if students have `status = 'active'` in `classlist_user` pivot table

### Scenario 2: Log shows "user preference disabled"

**Problem:** User has disabled email notifications

**Solution:**
```bash
# Check user preferences
php artisan tinker
>>> $user = App\Models\User::where('email', 'student@example.com')->first();
>>> $prefs = App\Models\NotificationPreference::where('user_id', $user->id)->first();
>>> $prefs->assignment_created_email
>>> exit
```

If `false`, update preferences:
```sql
UPDATE notification_preferences 
SET assignment_created_email = 1
WHERE user_id = XXX;
```

### Scenario 3: Log shows "Notification class does not exist"

**Problem:** Notification class file missing

**Solution:**
```bash
# Check if file exists
ls -la app/Notifications/AssignmentCreatedNotification.php
```

If missing, upload the notification files.

### Scenario 4: No logs at all

**Problem:** Code not being executed

**Solution:**
- Verify files were uploaded correctly
- Check if using correct class (maybe cached old version)
- Clear all caches

### Scenario 5: Log shows success but no jobs in queue

**Problem:** Queue connection issue

**Solution:**
```bash
# Verify queue config
php artisan tinker
>>> config('queue.default')
# Should be: "database"
>>> exit
```

If not "database":
- Fix `.env` file
- Run `php artisan config:clear && php artisan config:cache`

---

## 📊 Expected Flow (After Fix)

1. **Create assignment via web** 
   → Log: "Sending assignment notifications, students_count: 5"

2. **NotificationService called for each student**
   → Log: "Attempting to send email notification, user_id: X"

3. **Notification queued**
   → Log: "Email notification queued successfully"

4. **Job added to database**
   → `queue:monitor` shows pending jobs

5. **Cron processes jobs**
   → Emails sent

6. **Students receive emails**
   → Success! ✅

---

## 🧪 Complete Test Script

Run this after deploying:

```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html

echo "=== BEFORE TEST ==="
echo "Current queue status:"
php artisan queue:monitor database

echo ""
echo "=== NOW: Create an assignment via web interface ==="
read -p "After creating assignment, press ENTER to continue..."

echo ""
echo "=== AFTER TEST ==="
echo "Check logs for debugging info:"
tail -50 storage/logs/laravel.log | grep -A 5 "Sending assignment"

echo ""
echo "Check queue:"
php artisan queue:monitor database

echo ""
echo "Process jobs manually (if any):"
php artisan queue:work database --stop-when-empty

echo ""
echo "Check final queue status:"
php artisan queue:monitor database

echo ""
echo "Check for failed jobs:"
php artisan queue:failed
```

---

## ✅ Success Indicators

After deploying, you should see:
- ✅ Log shows "Sending X notifications" with student count > 0
- ✅ Log shows "Email notification queued successfully" for each student
- ✅ Queue monitor shows pending jobs equal to student count
- ✅ After cron or manual processing, queue shows 0 jobs
- ✅ Students receive emails

---

## 🆘 If Still Not Working

Send me:
1. **Output of:** `tail -100 storage/logs/laravel.log` (after creating assignment)
2. **Output of:** `php artisan queue:monitor database`
3. **Student count:** How many students are in the class?
4. **Queue test result:** Does `php artisan queue:test` still work?

---

**Deploy the updated files and test immediately!** The logging will tell us exactly what's happening. 🚀

**Last Updated:** February 2, 2026
