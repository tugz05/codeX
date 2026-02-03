# ⚡ Class Record Module - Quick Setup

## 📊 **What is This?**

A complete gradebook system for instructors to:
- ✅ Define grade components (Assignments, Quizzes, Exams, etc.)
- ✅ Set percentage weights for each component
- ✅ Add individual items within components
- ✅ Enter student points/scores
- ✅ **Automatic final grade calculation**
- ✅ Export to CSV

---

## 🚀 **Installation:**

### **Step 1: Run Migration**
```bash
cd C:\laragon\www\codeX
php artisan migrate
```

**Creates 3 tables:**
- `grade_components` - Categories (Assignments 40%, Quizzes 30%, etc.)
- `grade_items` - Individual items (Quiz 1, Quiz 2, etc.)
- `student_grades` - Student scores

### **Step 2: Test Routes**
```bash
php artisan route:list | grep class-record
```

**Expected:** 9 routes for class-record

### **Step 3: Access in Browser**
```
http://localhost:8000/instructor/classlist/{classlist_id}/class-record
```

---

## 📁 **Files Created:**

### **Backend:**
```
✅ database/migrations/2026_02_03_110000_create_grade_components_table.php
✅ database/migrations/2026_02_03_110001_create_grade_items_table.php
✅ database/migrations/2026_02_03_110002_create_student_grades_table.php
✅ app/Models/GradeComponent.php
✅ app/Models/GradeItem.php
✅ app/Models/StudentGrade.php
✅ app/Http/Controllers/ClassRecordController.php
✅ routes/web.php (updated)
```

### **Frontend:** (Need to create Vue component)
```
⏳ resources/js/pages/Instructor/ClassRecord/Index.vue (TODO)
```

---

## 💡 **How It Works:**

### **1. Create Grade Components**
Example structure:
```
Component: Assignments (40% weight)
  ├─ Assignment 1 (100 points, Date: Feb 10)
  ├─ Assignment 2 (100 points, Date: Feb 17)
  └─ Assignment 3 (100 points, Date: Feb 24)
  
Component: Quizzes (30% weight)
  ├─ Quiz 1 (50 points, Date: Feb 12)
  ├─ Quiz 2 (50 points, Date: Feb 19)
  └─ Quiz 3 (50 points, Date: Feb 26)
  
Component: Exams (30% weight)
  ├─ Midterm (100 points, Date: Mar 5)
  └─ Final (100 points, Date: Apr 15)
```

### **2. Enter Student Grades**
```
Student: John Doe
  Assignments: 95/100, 90/100, 88/100 = 273/300 = 91%
  Quizzes: 48/50, 45/50, 46/50 = 139/150 = 93%
  Exams: 88/100, 92/100 = 180/200 = 90%
```

### **3. Automatic Calculation**
```
Assignments: 91% × 40% weight = 36.4 points
Quizzes:     93% × 30% weight = 27.9 points
Exams:       90% × 30% weight = 27.0 points

Final Grade = 36.4 + 27.9 + 27.0 = 91.3%
Letter Grade = A
```

---

## 📊 **Grade Scale:**

| Grade | Range | Description |
|-------|-------|-------------|
| A+    | 97-100% | Outstanding |
| A     | 93-96%  | Excellent |
| A-    | 90-92%  | Very Good |
| B+    | 87-89%  | Good |
| B     | 83-86%  | Above Average |
| B-    | 80-82%  | Average |
| C     | 70-79%  | Below Average |
| D     | 60-69%  | Poor |
| F     | < 60%   | Failing |

---

## 🔌 **API Endpoints:**

### **View Class Record:**
```
GET /instructor/classlist/{classlist}/class-record
```

### **Manage Components:**
```
POST   /instructor/classlist/{classlist}/class-record/components
PUT    /instructor/class-record/components/{component}
DELETE /instructor/class-record/components/{component}
```

### **Manage Items:**
```
POST   /instructor/class-record/components/{component}/items
PUT    /instructor/class-record/items/{item}
DELETE /instructor/class-record/items/{item}
```

### **Update Grades:**
```
POST /instructor/class-record/items/{item}/grades
Body: { 
  "grades": [
    {"user_id": 1, "points": 45.5, "remarks": "Good work"},
    {"user_id": 2, "points": 38.0}
  ]
}
```

### **Export CSV:**
```
GET /instructor/classlist/{classlist}/class-record/export
```

---

## 🧪 **Quick Test:**

```bash
php artisan tinker
```

```php
// Create a test component
$component = \App\Models\GradeComponent::create([
    'classlist_id' => 'your_classlist_id',
    'name' => 'Quizzes',
    'weight' => 30.00,
    'order' => 1,
]);

// Add an item
$item = \App\Models\GradeItem::create([
    'grade_component_id' => $component->id,
    'name' => 'Quiz 1',
    'max_points' => 50,
    'date' => '2026-02-10',
    'order' => 1,
]);

// Add a grade
\App\Models\StudentGrade::create([
    'grade_item_id' => $item->id,
    'user_id' => 1, // Replace with actual student ID
    'points' => 45.5,
    'graded_at' => now(),
    'graded_by' => 1, // Replace with instructor ID
]);

// Check it
\App\Models\StudentGrade::with(['gradeItem', 'student'])->first();
```

---

## 📤 **Export Example:**

CSV output includes:
```
Student ID, Name, 
Assignment 1 (100), Assignment 2 (100), Assignments Total, Assignments %,
Quiz 1 (50), Quiz 2 (50), Quizzes Total, Quizzes %,
Midterm (100), Final (100), Exams Total, Exams %,
Final Grade, Letter Grade
```

---

## 🎨 **Frontend TODO:**

Need to create Vue component with:

1. **Component Management Section:**
   - Add/Edit/Delete components
   - Set weights
   - Drag to reorder

2. **Items Management:**
   - Add/Edit/Delete items per component
   - Set max points and dates
   - Drag to reorder within component

3. **Grade Entry Table:**
   - Students as rows
   - Items as columns
   - Input fields for points
   - Auto-save
   - Show totals and percentages

4. **Summary View:**
   - Final grades per student
   - Component breakdowns
   - Export button

---

## ✅ **Success Checklist:**

- [ ] Migration runs without errors
- [ ] Can access `/instructor/classlist/{id}/class-record`
- [ ] Can create grade components
- [ ] Can add grade items
- [ ] Can enter student grades
- [ ] Final grades calculate correctly
- [ ] Can export to CSV
- [ ] All timestamps use Philippine Time (UTC+08:00)

---

## 📚 **Full Documentation:**

See `CLASS-RECORD-MODULE.md` for:
- Complete database structure
- Detailed API documentation
- Calculation formulas
- Usage examples
- Testing procedures

---

## 🚀 **Deploy to Hostinger:**

```bash
# Upload files
# Then on server:
cd /home/u775863429/domains/nemsu-codex.online/public_html
php artisan migrate
php artisan config:cache
```

---

**Ready to build the frontend! Backend is complete.** 📊
