# 📋 Upload Checklist for Class Record Module

## ✅ Frontend Routing - COMPLETE
- ✅ Ziggy routes generated
- ✅ `ziggy.js` contains all 9 class-record routes
- ✅ Activities/Index.vue uses correct route syntax
- ✅ Calculator icon imported
- ✅ TypeScript routing is correct

---

## ⏳ Backend Files - NEED TO UPLOAD

### **1. Models (4 files)**

**Upload to:** `/home/u775863429/domains/nemsu-codex.online/public_html/app/Models/`

- [ ] `GradeComponent.php` (NEW)
- [ ] `GradeItem.php` (NEW)
- [ ] `StudentGrade.php` (NEW)
- [ ] `Classlist.php` (REPLACE - has new gradeComponents() relationship)

### **2. Controller (1 file)**

**Upload to:** `/home/u775863429/domains/nemsu-codex.online/public_html/app/Http/Controllers/`

- [ ] `ClassRecordController.php` (NEW - already has null safety fixes)

### **3. Migrations (3 files)**

**Upload to:** `/home/u775863429/domains/nemsu-codex.online/public_html/database/migrations/`

- [ ] `2026_02_03_110000_create_grade_components_table.php`
- [ ] `2026_02_03_110001_create_grade_items_table.php`
- [ ] `2026_02_03_110002_create_student_grades_table.php`

### **4. Frontend (2 files)**

**Upload to:** `/home/u775863429/domains/nemsu-codex.online/public_html/resources/js/pages/Instructor/`

- [ ] `ClassRecord/Index.vue` (NEW - create ClassRecord folder first)
- [ ] `Activities/Index.vue` (REPLACE - has Class Record tab)

### **5. Routes (1 file)**

**Upload to:** `/home/u775863429/domains/nemsu-codex.online/public_html/routes/`

- [ ] `web.php` (REPLACE - has 9 new class-record routes)

---

## 🔧 After Uploading Files

### **Run in SSH:**

```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html

# Run migrations
php artisan migrate --path=database/migrations/2026_02_03_110000_create_grade_components_table.php
php artisan migrate --path=database/migrations/2026_02_03_110001_create_grade_items_table.php
php artisan migrate --path=database/migrations/2026_02_03_110002_create_student_grades_table.php

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

# Rebuild frontend
npm run build

# Verify tables exist
php artisan tinker
>>> DB::table('grade_components')->count();
>>> exit;
```

---

## ✅ Verification Steps

1. **Check files uploaded:**
   ```bash
   ls -la app/Models/Grade*.php
   ls -la app/Http/Controllers/ClassRecordController.php
   ```

2. **Verify migrations ran:**
   ```bash
   php artisan migrate:status | grep grade
   ```

3. **Check routes:**
   ```bash
   php artisan route:list --name=class-record
   ```

4. **Test in browser:**
   - Go to class
   - Click "Class Record" tab
   - Should load without 500 error!

---

## 📦 Total Files to Upload: 11

- 4 Models
- 1 Controller
- 3 Migrations
- 2 Vue Components
- 1 Routes file

---

**Current Status:**
- ✅ Frontend routing: READY
- ✅ TypeScript: READY
- ⏳ Backend: NEEDS UPLOAD
- ⏳ Database: NEEDS MIGRATION

**Next Step:** Upload the 11 files via File Manager, then run the SSH commands!
