# Quiz and Examination Feature Documentation

## Overview

The codeX platform now supports **Quizzes** and **Examinations** as hybrid features alongside coding activities. Students can take quizzes and exams with various question types, and instructors can create and manage them.

## Features

### For Instructors
- ✅ Create quizzes and examinations
- ✅ Add multiple question types (Multiple Choice, True/False, Short Answer, Essay)
- ✅ Set time limits and attempt limits
- ✅ Configure scheduling (start/end dates)
- ✅ Shuffle questions option
- ✅ Show/hide correct answers
- ✅ View student attempts and scores

### For Students
- ✅ View available quizzes and examinations
- ✅ Start timed quizzes/exams
- ✅ Answer questions with auto-save
- ✅ Navigate between questions
- ✅ Submit attempts
- ✅ View results and scores

## Database Structure

### Tables Created
1. **quizzes** - Quiz metadata and settings
2. **examinations** - Examination metadata and settings
3. **questions** - Questions (polymorphic: can belong to quiz or exam)
4. **quiz_attempts** - Student quiz attempts
5. **exam_attempts** - Student exam attempts
6. **question_answers** - Answers to questions (polymorphic: can belong to quiz_attempt or exam_attempt)

## Question Types

1. **Multiple Choice** - Select one correct answer from options
2. **True/False** - Select true or false
3. **Short Answer** - Text input answer
4. **Essay** - Long-form text answer

## Routes

### Instructor Routes
```
GET    /instructor/classlist/{classlist}/quizzes
POST   /instructor/classlist/{classlist}/quizzes
GET    /instructor/classlist/{classlist}/quizzes/{quiz}
PUT    /instructor/classlist/{classlist}/quizzes/{quiz}
DELETE /instructor/classlist/{classlist}/quizzes/{quiz}

GET    /instructor/classlist/{classlist}/examinations
POST   /instructor/classlist/{classlist}/examinations
GET    /instructor/classlist/{classlist}/examinations/{examination}
PUT    /instructor/classlist/{classlist}/examinations/{examination}
DELETE /instructor/classlist/{classlist}/examinations/{examination}
```

### Student Routes
```
GET    /student/classlist/{classlist}/quizzes
GET    /student/classlist/{classlist}/quizzes/{quiz}
POST   /student/classlist/{classlist}/quizzes/{quiz}/start
GET    /student/classlist/{classlist}/quizzes/{quiz}/attempt/{attempt}
POST   /student/classlist/{classlist}/quizzes/{quiz}/attempt/{attempt}/answer
POST   /student/classlist/{classlist}/quizzes/{quiz}/attempt/{attempt}/submit
GET    /student/classlist/{classlist}/quizzes/{quiz}/attempt/{attempt}/result

GET    /student/classlist/{classlist}/examinations
GET    /student/classlist/{classlist}/examinations/{examination}
POST   /student/classlist/{classlist}/examinations/{examination}/start
GET    /student/classlist/{classlist}/examinations/{examination}/attempt/{attempt}
POST   /student/classlist/{classlist}/examinations/{examination}/attempt/{attempt}/answer
POST   /student/classlist/{classlist}/examinations/{examination}/attempt/{attempt}/submit
GET    /student/classlist/{classlist}/examinations/{examination}/attempt/{attempt}/result
```

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Access Quizzes/Exams

**For Instructors:**
- Navigate to a classlist
- Access quizzes/examinations from the classlist management page
- Create new quizzes/exams with questions

**For Students:**
- Navigate to a classlist
- Access quizzes/examinations from the student dashboard
- Start and take quizzes/exams

## Usage Examples

### Creating a Quiz (Instructor)

1. Go to `/instructor/classlist/{id}/quizzes`
2. Click "Create Quiz"
3. Fill in quiz details:
   - Title
   - Description
   - Time limit (optional)
   - Attempts allowed
   - Start/End dates
   - Settings (shuffle questions, show answers)
4. Add questions:
   - Question text
   - Question type
   - Points
   - Options (for multiple choice)
   - Correct answer(s)
   - Explanation (optional)
5. Publish the quiz

### Taking a Quiz (Student)

1. Go to `/student/classlist/{id}/quizzes`
2. Click "Start" on an available quiz
3. Answer questions (answers auto-save)
4. Navigate between questions
5. Submit when finished
6. View results

## Key Features

### Auto-Grading
- Multiple choice and true/false questions are automatically graded
- Short answer questions can be auto-graded if correct answers are provided
- Essay questions require manual grading (can be extended)

### Time Management
- Time limits are enforced
- Timer displays remaining time
- Auto-submit when time expires

### Attempt Management
- Students can have multiple attempts (configurable)
- Each attempt is tracked separately
- Best score tracking

### Answer Persistence
- Answers are saved automatically as students type
- Students can navigate between questions without losing answers
- Resume in-progress attempts

## Next Steps / Enhancements

### Recommended Additions:
1. **Instructor Pages** - Create Vue pages for instructors to create/edit quizzes and exams
2. **Result Pages** - Create result display pages for students
3. **Question Bank** - Allow instructors to save and reuse questions
4. **Manual Grading** - Interface for grading essay questions
5. **Analytics** - Show quiz/exam performance analytics
6. **Proctoring** - Implement proctoring features for examinations
7. **Question Import/Export** - Bulk import questions from CSV/JSON

## Notes

- Quizzes and Examinations share the same question structure
- The main difference is in settings (exams typically don't show correct answers)
- All attempts are stored for review and analytics
- Questions use polymorphic relationships for flexibility

## Support

For issues or questions, refer to:
- Controllers: `app/Http/Controllers/QuizController.php`, `ExaminationController.php`
- Models: `app/Models/Quiz.php`, `Examination.php`, `Question.php`
- Vue Pages: `resources/js/pages/Student/Quizzes/`, `Student/Examinations/`
