# 🚀 Deploy Class Record Module to Hostinger

## 📋 **Files to Upload to Production**

### **1. New Model Files (upload to `/home/u775863429/domains/nemsu-codex.online/public_html/app/Models/`):**
- `GradeComponent.php`
- `GradeItem.php`
- `StudentGrade.php`

### **2. Updated Model File (replace existing):**
- `Classlist.php` (contains new `gradeComponents()` relationship)

### **3. New Controller (upload to `/home/u775863429/domains/nemsu-codex.online/public_html/app/Http/Controllers/`):**
- `ClassRecordController.php`

### **4. New Migration Files (upload to `/home/u775863429/domains/nemsu-codex.online/public_html/database/migrations/`):**
- `2026_02_03_110000_create_grade_components_table.php`
- `2026_02_03_110001_create_grade_items_table.php`
- `2026_02_03_110002_create_student_grades_table.php`

### **5. New Vue Component (upload to `/home/u775863429/domains/nemsu-codex.online/public_html/resources/js/pages/Instructor/ClassRecord/`):**
- Create folder: `ClassRecord`
- Upload: `Index.vue`

### **6. Updated Vue Component (replace existing):**
- `resources/js/pages/Instructor/Activities/Index.vue` (contains new Class Record tab)

### **7. Updated Routes (replace existing):**
- `routes/web.php` (contains 9 new class-record routes)

---

## 🔧 **Step-by-Step Deployment**

### **Step 1: Upload Files via FTP/File Manager**

1. **Connect to your Hostinger File Manager** or FTP client
2. **Navigate to:** `/home/u775863429/domains/nemsu-codex.online/public_html/`
3. **Upload all files** listed above to their respective folders

---

### **Step 2: Run Migrations on Production**

**SSH into your server and run:**

```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html

# Run the 3 new migrations
php artisan migrate --path=database/migrations/2026_02_03_110000_create_grade_components_table.php
php artisan migrate --path=database/migrations/2026_02_03_110001_create_grade_items_table.php
php artisan migrate --path=database/migrations/2026_02_03_110002_create_student_grades_table.php
```

**Expected Output:**
```
INFO  Running migrations.  
2026_02_03_110000_create_grade_components_table .......... DONE
INFO  Running migrations.  
2026_02_03_110001_create_grade_items_table ............... DONE
INFO  Running migrations.  
2026_02_03_110002_create_student_grades_table ............ DONE
```

---

### **Step 3: Clear All Caches**

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

---

### **Step 4: Rebuild Frontend Assets**

```bash
npm run build
```

**This will compile your Vue components for production.**

---

### **Step 5: Verify Routes**

```bash
php artisan route:list --name=class-record
```

**You should see all 9 routes:**
```
GET|HEAD  instructor/classlist/{classlist}/class-record
POST      instructor/classlist/{classlist}/class-record/components
GET|HEAD  instructor/classlist/{classlist}/class-record/export
PUT       instructor/class-record/components/{component}
DELETE    instructor/class-record/components/{component}
POST      instructor/class-record/components/{component}/items
PUT       instructor/class-record/items/{item}
DELETE    instructor/class-record/items/{item}
POST      instructor/class-record/items/{item}/grades
```

---

### **Step 6: Check Database Tables**

```bash
php artisan tinker
```

Then in tinker:
```php
DB::table('grade_components')->count();  // Should return 0 (empty but exists)
DB::table('grade_items')->count();        // Should return 0
DB::table('student_grades')->count();     // Should return 0
exit;
```

---

## ✅ **Quick Command Summary**

**Copy and paste this entire block into SSH:**

```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html && \
php artisan migrate --path=database/migrations/2026_02_03_110000_create_grade_components_table.php && \
php artisan migrate --path=database/migrations/2026_02_03_110001_create_grade_items_table.php && \
php artisan migrate --path=database/migrations/2026_02_03_110002_create_student_grades_table.php && \
php artisan config:clear && \
php artisan route:clear && \
php artisan view:clear && \
php artisan cache:clear && \
echo "✅ Migrations and caches cleared successfully!"
```

---

## 🎯 **Test the Module**

1. **Go to your site:** `https://nemsu-codex.online`
2. **Login as instructor**
3. **Go to any class**
4. **Click "Class Record" tab**
5. **Should load without 500 error!**

---

## ⚠️ **Troubleshooting**

### **If you still get 500 error:**

1. **Check Laravel logs:**
   ```bash
   tail -50 /home/u775863429/domains/nemsu-codex.online/public_html/storage/logs/laravel.log
   ```

2. **Check if models exist:**
   ```bash
   ls -la app/Models/ | grep Grade
   ```
   Should show:
   - GradeComponent.php
   - GradeItem.php
   - StudentGrade.php

3. **Check if controller exists:**
   ```bash
   ls -la app/Http/Controllers/ClassRecordController.php
   ```

4. **Verify migrations ran:**
   ```bash
   php artisan migrate:status | grep grade
   ```

---

## 📂 **Complete File List Summary**

**Models (3 new + 1 updated):**
- ✅ `app/Models/GradeComponent.php` (NEW)
- ✅ `app/Models/GradeItem.php` (NEW)
- ✅ `app/Models/StudentGrade.php` (NEW)
- ✅ `app/Models/Classlist.php` (UPDATED - has gradeComponents relationship)

**Controller (1 new):**
- ✅ `app/Http/Controllers/ClassRecordController.php` (NEW)

**Migrations (3 new):**
- ✅ `database/migrations/2026_02_03_110000_create_grade_components_table.php`
- ✅ `database/migrations/2026_02_03_110001_create_grade_items_table.php`
- ✅ `database/migrations/2026_02_03_110002_create_student_grades_table.php`

**Frontend (1 new + 1 updated):**
- ✅ `resources/js/pages/Instructor/ClassRecord/Index.vue` (NEW)
- ✅ `resources/js/pages/Instructor/Activities/Index.vue` (UPDATED - has Class Record tab)

**Routes:**
- ✅ `routes/web.php` (UPDATED - has 9 new class-record routes)

---

## 🚀 **Ready!**

After uploading files, running migrations, and building assets, your Class Record module will be **live on production**! 🎉

**Need help? Check the logs or let me know what error you see!**
