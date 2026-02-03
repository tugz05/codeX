# 📊 Class Record Module - Complete Documentation

## 🎯 **Overview:**

A comprehensive gradebook/class record system for instructors to manage student grades and calculate final grades.

---

## ✨ **Features:**

### **1. Grade Components**
- Define grading categories (e.g., Assignments, Quizzes, Exams)
- Assign percentage weights to each component
- Reorder components

### **2. Grade Items**
- Add individual items within each component
- Set maximum points for each item
- Assign dates to items
- Reorder items within components

### **3. Student Grades**
- Enter points for each student for each item
- Add remarks/notes
- Track who graded and when
- Automatic percentage calculation

### **4. Final Grade Calculation**
- Automatic weighted grade calculation
- Component breakdown view
- Letter grade assignment
- Real-time updates

### **5. Export**
- Export to CSV
- Includes all components, items, and final grades
- Ready for Excel/Google Sheets

---

## 📁 **Database Structure:**

### **Tables Created:**

#### **1. `grade_components`**
```
- id (bigint)
- classlist_id (string, FK)
- name (string) - e.g., "Assignments"
- weight (decimal) - e.g., 30.00 (30%)
- description (text, nullable)
- order (integer) - display order
- timestamps
```

#### **2. `grade_items`**
```
- id (bigint)
- grade_component_id (bigint, FK)
- name (string) - e.g., "Quiz 1"
- max_points (decimal) - e.g., 50.00
- date (date, nullable)
- description (text, nullable)
- order (integer) - display order
- timestamps
```

#### **3. `student_grades`**
```
- id (bigint)
- grade_item_id (bigint, FK)
- user_id (bigint, FK)
- points (decimal, nullable) - e.g., 45.00
- remarks (text, nullable)
- graded_at (timestamp, nullable)
- graded_by (bigint, FK to users, nullable)
- timestamps
- UNIQUE(grade_item_id, user_id)
```

---

## 🗂️ **Files Created:**

### **Migrations:**
1. `2026_02_03_110000_create_grade_components_table.php`
2. `2026_02_03_110001_create_grade_items_table.php`
3. `2026_02_03_110002_create_student_grades_table.php`

### **Models:**
1. `app/Models/GradeComponent.php`
2. `app/Models/GradeItem.php`
3. `app/Models/StudentGrade.php`

### **Controller:**
- `app/Http/Controllers/ClassRecordController.php`

### **Routes:**
- Added to `routes/web.php` (instructor middleware group)

---

## 🚀 **Installation:**

### **Step 1: Run Migration**
```bash
php artisan migrate
```

### **Step 2: Test Routes**
```bash
php artisan route:list | grep class-record
```

**Expected output:**
```
GET    /instructor/classlist/{classlist}/class-record
POST   /instructor/classlist/{classlist}/class-record/components
PUT    /instructor/class-record/components/{component}
DELETE /instructor/class-record/components/{component}
POST   /instructor/class-record/components/{component}/items
PUT    /instructor/class-record/items/{item}
DELETE /instructor/class-record/items/{item}
POST   /instructor/class-record/items/{item}/grades
GET    /instructor/classlist/{classlist}/class-record/export
```

---

## 💡 **Usage Examples:**

### **Create Grade Components:**
```php
// Example: 40% Assignments, 30% Quizzes, 30% Exams
GradeComponent::create([
    'classlist_id' => 'abc123',
    'name' => 'Assignments',
    'weight' => 40.00,
    'order' => 1,
]);

GradeComponent::create([
    'classlist_id' => 'abc123',
    'name' => 'Quizzes',
    'weight' => 30.00,
    'order' => 2,
]);

GradeComponent::create([
    'classlist_id' => 'abc123',
    'name' => 'Exams',
    'weight' => 30.00,
    'order' => 3,
]);
```

### **Add Grade Items:**
```php
$component = GradeComponent::where('name', 'Quizzes')->first();

GradeItem::create([
    'grade_component_id' => $component->id,
    'name' => 'Quiz 1',
    'max_points' => 50,
    'date' => '2026-02-10',
    'order' => 1,
]);

GradeItem::create([
    'grade_component_id' => $component->id,
    'name' => 'Quiz 2',
    'max_points' => 50,
    'date' => '2026-02-17',
    'order' => 2,
]);
```

### **Enter Student Grades:**
```php
StudentGrade::create([
    'grade_item_id' => $quizItem->id,
    'user_id' => $student->id,
    'points' => 45.50,
    'remarks' => 'Good performance',
    'graded_at' => now(),
    'graded_by' => $instructor->id,
]);
```

### **Calculate Final Grade:**
```php
$finalGrade = $controller->calculateFinalGrade($studentId, $gradeComponents);

// Returns:
[
    'final_grade' => 87.5,
    'total_weighted_score' => 87.5,
    'letter_grade' => 'B+',
    'breakdown' => [
        [
            'component' => 'Assignments',
            'points' => 180,
            'max_points' => 200,
            'percentage' => 90.0,
            'weighted_score' => 36.0, // 90% of 40%
            'weight' => 40.0,
        ],
        // ... other components
    ]
]
```

---

## 📊 **Grade Calculation Logic:**

### **Component Percentage:**
```
Component % = (Student Points / Max Points) × 100
```

### **Weighted Score:**
```
Weighted Score = (Component % / 100) × Component Weight
```

### **Final Grade:**
```
Final Grade = Sum of All Weighted Scores
```

### **Example:**
```
Assignments: 180/200 = 90% → 90% × 40% weight = 36 points
Quizzes:     140/150 = 93% → 93% × 30% weight = 28 points  
Exams:       80/100  = 80% → 80% × 30% weight = 24 points

Final Grade = 36 + 28 + 24 = 88%
Letter Grade = B+
```

---

## 🎨 **Letter Grade Scale:**

| Grade | Percentage Range |
|-------|------------------|
| A+    | 97-100%          |
| A     | 93-96%           |
| A-    | 90-92%           |
| B+    | 87-89%           |
| B     | 83-86%           |
| B-    | 80-82%           |
| C+    | 77-79%           |
| C     | 73-76%           |
| C-    | 70-72%           |
| D+    | 67-69%           |
| D     | 63-66%           |
| D-    | 60-62%           |
| F     | Below 60%        |

---

## 🔌 **API Endpoints:**

### **View Class Record:**
```
GET /instructor/classlist/{classlist}/class-record
```

### **Component Management:**
```
POST   /instructor/classlist/{classlist}/class-record/components
PUT    /instructor/class-record/components/{component}
DELETE /instructor/class-record/components/{component}
```

### **Item Management:**
```
POST   /instructor/class-record/components/{component}/items
PUT    /instructor/class-record/items/{item}
DELETE /instructor/class-record/items/{item}
```

### **Grade Management:**
```
POST /instructor/class-record/items/{item}/grades

Body:
{
  "grades": [
    {
      "user_id": 1,
      "points": 45.5,
      "remarks": "Great work"
    },
    {
      "user_id": 2,
      "points": 38.0,
      "remarks": null
    }
  ]
}
```

### **Export:**
```
GET /instructor/classlist/{classlist}/class-record/export
```

---

## 📈 **Statistics & Analytics:**

### **Model Methods Available:**

**GradeItem:**
- `getAverageScore()` - Average score for the item
- `getPassingRate()` - % of students who passed (≥60%)

**GradeComponent:**
- `getTotalMaxPointsAttribute` - Sum of max points for all items
- `getStudentTotalPoints($userId)` - Student's total points
- `getStudentPercentage($userId)` - Student's percentage

**StudentGrade:**
- `getPercentageAttribute` - Percentage score
- `isPassing()` - Check if ≥60%
- `getLetterGrade()` - Get letter grade

---

## 📤 **CSV Export Format:**

```csv
Student ID, Last Name, First Name, Middle Name, 
Assignments - Assignment 1 (100), Assignments - Assignment 2 (100), 
Assignments Total, Assignments %, 
Quizzes - Quiz 1 (50), Quizzes - Quiz 2 (50),
Quizzes Total, Quizzes %,
Exams - Midterm (100), Exams - Final (100),
Exams Total, Exams %,
Final Grade, Letter Grade

2021-1234, Doe, John, Smith,
95, 90, 185, 92.5,
48, 45, 93, 93.0,
88, 92, 180, 90.0,
91.75, A
```

---

## 🎯 **Frontend Integration:**

### **Access Class Record:**
```
/instructor/classlist/{classlist_id}/class-record
```

### **Features Needed in Frontend:**

1. **Component Management:**
   - Add/Edit/Delete components
   - Set weights
   - Reorder components

2. **Item Management:**
   - Add/Edit/Delete items within components
   - Set max points and dates
   - Reorder items

3. **Grade Entry:**
   - Table view with students as rows
   - Items as columns
   - Input fields for points
   - Remarks field
   - Bulk save

4. **Grade Display:**
   - Component totals per student
   - Component percentages
   - Final grade calculation
   - Letter grade display

5. **Export:**
   - Download as CSV button

---

## ⚠️ **Important Notes:**

### **1. Weight Management:**
- Component weights should total 100%
- System allows flexibility (can be < or > 100%)
- Display warning if not 100%

### **2. Grade Validation:**
- Points cannot exceed max_points
- Frontend should validate before submission
- Backend validates in controller

### **3. Missing Grades:**
- Null/empty points = not graded yet
- Affects final grade calculation
- Show as "-" or "N/A" in UI

### **4. Timezone:**
- All dates use Asia/Manila (UTC+08:00)
- `graded_at` timestamp shows when grade was entered

---

## ✅ **Testing Checklist:**

- [ ] Run migrations successfully
- [ ] Create grade components
- [ ] Add grade items
- [ ] Enter student grades
- [ ] View final grade calculations
- [ ] Export to CSV
- [ ] Test weight distribution (total 100%)
- [ ] Test with missing grades
- [ ] Test bulk grade update
- [ ] Verify letter grades
- [ ] Test delete cascades

---

## 🎨 **UI/UX Recommendations:**

1. **Table Layout:**
   - Sticky header and first column
   - Responsive design
   - Mobile-friendly input

2. **Color Coding:**
   - Red: Failing grades (< 60%)
   - Yellow: Warning (60-75%)
   - Green: Good (75-90%)
   - Blue: Excellent (90-100%)

3. **Quick Actions:**
   - Quick edit inline
   - Keyboard navigation
   - Auto-save
   - Undo/Redo

4. **Visual Feedback:**
   - Show totals dynamically
   - Highlight unsaved changes
   - Loading states
   - Success/error toasts

---

## 🚀 **Next Steps:**

1. **Run migration:** `php artisan migrate`
2. **Create frontend Vue component** (see separate doc)
3. **Add navigation link** in instructor layout
4. **Test with sample data**
5. **Deploy to production**

---

**Complete class record system ready for integration!** 📊
