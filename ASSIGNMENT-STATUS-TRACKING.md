# 📋 Automatic Assignment Status Tracking

## 🎯 **Feature Overview:**

Automatic tracking and updating of assignment statuses based on deadlines and submission times.

---

## 📊 **Assignment Statuses:**

| Status | Label | When Applied | Color |
|--------|-------|--------------|-------|
| `assigned` | **Assigned** | Work to be done (default when assignment created) | Blue |
| `turned_in` | **Turned In** | Submitted before deadline | Green |
| `missing` | **Missing** | Past due date, not submitted | Red |
| `late` | **Turned In: Done Late** | Submitted after deadline | Orange |
| `graded` | **Graded** | Reviewed and returned by teacher | Purple |

---

## ⚙️ **How It Works:**

### **Automatic Status Updates:**

The system runs every minute (via cron) and:

1. **Checks all assignments** with due dates
2. **For each student:**
   - If **submitted before deadline** → `turned_in` ✅
   - If **submitted after deadline** → `late` ⚠️
   - If **past due and not submitted** → `missing` ❌
   - If **graded by teacher** → `graded` 📝
   - If **not yet due** → `assigned` 📋

3. **Creates missing records** for students who didn't submit past-due assignments

---

## 🗂️ **Files Created/Modified:**

### **1. Migration:**
```
database/migrations/2026_02_03_100000_update_assignment_submission_status_field.php
```
- Updates `status` enum to include all new statuses
- Migrates existing data

### **2. Command:**
```
app/Console/Commands/UpdateAssignmentStatusCommand.php
```
- Automatically updates assignment statuses
- Runs every minute via cron

### **3. Model Updates:**
```
app/Models/AssignmentSubmission.php
```
- Added status constants
- Added helper methods:
  - `isLate()` - Check if submission is late
  - `isMissing()` - Check if submission is missing
  - `isGraded()` - Check if graded
  - `isTurnedIn()` - Check if turned in (on time or late)
  - `getStatusColor()` - Get badge color for UI
  - `getStatusLabel()` - Get human-readable label

### **4. Cron Script:**
```
queue-cron.php
```
- Now runs `assignments:update-status` command every minute

---

## 🚀 **Installation Steps:**

### **1. Run Migration:**
```bash
php artisan migrate
```

This will update the `assignment_submissions` table to support new statuses.

### **2. Test Command Manually:**
```bash
php artisan assignments:update-status
```

**Expected output:**
```
Checking assignment statuses...
Assignment statuses updated: X records updated, Y marked as missing
```

### **3. Upload Updated Files:**

Upload these files to Hostinger:
- `queue-cron.php` (updated)
- `app/Console/Commands/UpdateAssignmentStatusCommand.php` (new)
- `app/Models/AssignmentSubmission.php` (updated)
- Migration file (new)

### **4. Run Migration on Server:**
```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html
php artisan migrate
```

### **5. Verify Cron is Running:**

The cron job will now automatically:
- ✅ Process email notifications
- ✅ Process messages
- ✅ **Update assignment statuses** (NEW!)

Check logs:
```bash
tail -20 storage/logs/cron.log
```

---

## 💡 **Usage in Code:**

### **Using Status Constants:**

```php
use App\Models\AssignmentSubmission;

// Check status
if ($submission->status === AssignmentSubmission::STATUS_MISSING) {
    // Handle missing assignment
}

// Using helper methods
if ($submission->isMissing()) {
    // Show warning to student
}

if ($submission->isLate()) {
    // Apply late penalty
}

// Get UI badge
$color = $submission->getStatusColor(); // 'red', 'green', etc.
$label = $submission->getStatusLabel(); // 'Missing', 'Turned In', etc.
```

### **Query By Status:**

```php
// Get all missing assignments for a student
$missingAssignments = AssignmentSubmission::where('user_id', $studentId)
    ->where('status', AssignmentSubmission::STATUS_MISSING)
    ->get();

// Get all late submissions
$lateSubmissions = AssignmentSubmission::where('assignment_id', $assignmentId)
    ->where('status', AssignmentSubmission::STATUS_LATE)
    ->get();

// Count by status
$statusCounts = AssignmentSubmission::where('assignment_id', $assignmentId)
    ->selectRaw('status, count(*) as count')
    ->groupBy('status')
    ->pluck('count', 'status');
```

---

## 📊 **Example Use Cases:**

### **1. Student Dashboard - Show Missing Assignments:**
```php
$missingAssignments = auth()->user()
    ->assignmentSubmissions()
    ->where('status', AssignmentSubmission::STATUS_MISSING)
    ->with('assignment')
    ->get();
```

### **2. Instructor Dashboard - Assignment Overview:**
```php
$assignment = Assignment::with(['submissions' => function ($query) {
    $query->selectRaw('assignment_id, status, count(*) as count')
          ->groupBy('assignment_id', 'status');
}])->find($id);

// Show:
// - 15 Turned In
// - 5 Missing
// - 3 Late
// - 2 Graded
```

### **3. Automatic Late Penalties:**
```php
// In your grading logic
if ($submission->isLate()) {
    $latePenalty = 0.10; // 10% penalty
    $finalScore = $submission->score * (1 - $latePenalty);
}
```

---

## 🧪 **Testing:**

### **Test Scenario 1: Assignment Past Due**
1. Create assignment with due date in the past
2. Enroll students
3. Run: `php artisan assignments:update-status`
4. **Expected:** All students without submissions marked as `missing`

### **Test Scenario 2: Late Submission**
1. Create assignment with due date yesterday
2. Student submits today
3. Run: `php artisan assignments:update-status`
4. **Expected:** Submission status changed to `late`

### **Test Scenario 3: On-Time Submission**
1. Create assignment with due date tomorrow
2. Student submits today
3. **Expected:** Submission status is `turned_in`

---

## 📈 **Cron Log Output:**

```
[2026-02-03 10:30:00] Queue processed
  Notifications queue: exit code 0
  Default queue: exit code 0
  Assignment status update: exit code 0
  Execution time: 456.78ms

[2026-02-03 10:31:00] Queue processed
  Notifications queue: exit code 0
  Default queue: exit code 0
  Assignment status update: exit code 0
  Execution time: 234.56ms
```

---

## 🎨 **Frontend Integration:**

### **Status Badge Component (Example):**

```vue
<template>
  <span
    :class="[
      'px-2 py-1 rounded-full text-xs font-semibold',
      statusColor
    ]"
  >
    {{ statusLabel }}
  </span>
</template>

<script setup>
const props = defineProps(['status']);

const statusColor = computed(() => {
  return {
    'assigned': 'bg-blue-100 text-blue-800',
    'turned_in': 'bg-green-100 text-green-800',
    'missing': 'bg-red-100 text-red-800',
    'late': 'bg-orange-100 text-orange-800',
    'graded': 'bg-purple-100 text-purple-800',
  }[props.status] || 'bg-gray-100 text-gray-800';
});

const statusLabel = computed(() => {
  return {
    'assigned': 'Assigned',
    'turned_in': 'Turned In',
    'missing': 'Missing',
    'late': 'Done Late',
    'graded': 'Graded',
  }[props.status] || 'Unknown';
});
</script>
```

---

## 🔔 **Future Enhancements:**

Consider adding:
- Email notifications for missing assignments
- Reminders before due date
- Automatic grade penalties for late submissions
- Analytics dashboard showing submission patterns
- Student notification when status changes to "graded"

---

## ✅ **Success Indicators:**

- ✅ Migration runs successfully
- ✅ Command executes without errors
- ✅ Cron log shows assignment status updates
- ✅ Students see "Missing" for overdue assignments
- ✅ Late submissions marked correctly
- ✅ UI shows proper status badges

---

**The system now automatically tracks and updates assignment statuses every minute!** 🎉
