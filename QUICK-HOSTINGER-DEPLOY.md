# ⚡ Quick Hostinger Deployment - Class Record Module

## 🎯 **3-Step Quick Deploy**

### **Step 1: Upload Files via File Manager**

**Go to Hostinger File Manager:** `/home/u775863429/domains/nemsu-codex.online/public_html/`

**Upload these files:**

| Local Path | Upload to Production Path |
|-----------|---------------------------|
| `app/Models/GradeComponent.php` | `app/Models/` |
| `app/Models/GradeItem.php` | `app/Models/` |
| `app/Models/StudentGrade.php` | `app/Models/` |
| `app/Models/Classlist.php` | `app/Models/` (replace) |
| `app/Http/Controllers/ClassRecordController.php` | `app/Http/Controllers/` |
| `database/migrations/2026_02_03_110000_create_grade_components_table.php` | `database/migrations/` |
| `database/migrations/2026_02_03_110001_create_grade_items_table.php` | `database/migrations/` |
| `database/migrations/2026_02_03_110002_create_student_grades_table.php` | `database/migrations/` |
| `resources/js/pages/Instructor/ClassRecord/Index.vue` | `resources/js/pages/Instructor/ClassRecord/` (create folder) |
| `resources/js/pages/Instructor/Activities/Index.vue` | `resources/js/pages/Instructor/Activities/` (replace) |
| `routes/web.php` | `routes/` (replace) |

---

### **Step 2: SSH Commands**

**Connect via SSH and run this ONE command:**

```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html && php artisan migrate --path=database/migrations/2026_02_03_110000_create_grade_components_table.php && php artisan migrate --path=database/migrations/2026_02_03_110001_create_grade_items_table.php && php artisan migrate --path=database/migrations/2026_02_03_110002_create_student_grades_table.php && php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear && npm run build
```

---

### **Step 3: Test**

1. Go to: `https://nemsu-codex.online`
2. Login as instructor
3. Open any class
4. Click **"Class Record"** tab
5. **Should work!** ✅

---

## ⚠️ **If Still Getting 500 Error**

**Check logs:**
```bash
tail -50 storage/logs/laravel.log
```

**Send me the error message!**

---

**That's it! 3 steps and you're done!** 🚀
