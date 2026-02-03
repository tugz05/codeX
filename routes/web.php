<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivitySubmissionController;
use App\Http\Controllers\ClasslistController;
use App\Http\Controllers\ClasslistUserController;
use App\Http\Controllers\ClassMessageController;
use App\Http\Controllers\CriteriaController;
use App\Http\Controllers\InstructorDashboardController;
use App\Http\Controllers\BatchSubmissionController;
use App\Http\Controllers\InstructorAnalyticsController;
use App\Http\Controllers\StudentProgressController;
use App\Http\Controllers\PerformanceReportController;
use App\Http\Controllers\StudentActivityController;
use App\Http\Controllers\StudentGradeController;
use App\Http\Controllers\StudentRunController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ExaminationController;
use App\Http\Controllers\StudentQuizController;
use App\Http\Controllers\StudentExaminationController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\StudentMaterialController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\StudentAssignmentController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\CommentController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\InstructorMiddleware;
use App\Http\Middleware\StudentMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;



// Home route
Route::get('/', [\App\Http\Controllers\LandingPageController::class, 'index'])->name('home');


// Instructor routes
Route::middleware([InstructorMiddleware::class])->prefix('instructor')->name('instructor.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [InstructorDashboardController::class, 'index'])->name('dashboard');

    // Classlist routes
    Route::get('/classlist', [ClasslistController::class, 'index'])->name('classlist');
    Route::post('/classlist/add', [ClasslistController::class, 'store'])->name('classlist.store');
    Route::put('/classlist/{classlist}', [ClasslistController::class, 'update'])->name('classlist.update');
    Route::post('/classlist/{classlist}/archive', [ClasslistController::class, 'archive'])->name('classlist.archive');
    Route::post('/classlist/{classlist}/restore', [ClasslistController::class, 'restore'])->name('classlist.restore');
    Route::delete('/classlist/{classlist}', [ClasslistController::class, 'destroy'])->name('classlist.destroy');
    Route::get('/classlist/{classlist}/students', [ClasslistController::class, 'students'])->name('classlist.students');
    Route::post('/classlist/{classlist}/students/invite', [ClasslistController::class, 'inviteStudent'])->name('classlist.students.invite');
    Route::delete('/classlist/{classlist}/students/{student}', [ClasslistController::class, 'removeStudent'])->name('classlist.students.remove');

    // Archived Classes
    Route::get('/archived-classes', [\App\Http\Controllers\Instructor\ArchivedClassesController::class, 'index'])->name('archived-classes.index');
    Route::post('/archived-classes/{classlist}/restore', [\App\Http\Controllers\Instructor\ArchivedClassesController::class, 'restore'])->name('archived-classes.restore');

    // Gradebook routes
    Route::get('/classlist/{classlist}/gradebook', [\App\Http\Controllers\GradebookController::class, 'index'])->name('gradebook.index');
    Route::get('/classlist/{classlist}/gradebook/export/excel', [\App\Http\Controllers\GradebookController::class, 'exportExcel'])->name('gradebook.export.excel');
    Route::get('/classlist/{classlist}/gradebook/export/pdf', [\App\Http\Controllers\GradebookController::class, 'exportPdf'])->name('gradebook.export.pdf');

    // Attendance routes
    Route::get('/classlist/{classlist}/attendance', [\App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/classlist/{classlist}/attendance', [\App\Http\Controllers\AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/{session}/edit', [\App\Http\Controllers\AttendanceController::class, 'edit'])->name('attendance.edit');
    Route::put('/attendance/{session}', [\App\Http\Controllers\AttendanceController::class, 'update'])->name('attendance.update');
    Route::delete('/attendance/{session}', [\App\Http\Controllers\AttendanceController::class, 'destroy'])->name('attendance.destroy');
    Route::get('/classlist/{classlist}/attendance/report', [\App\Http\Controllers\AttendanceController::class, 'report'])->name('attendance.report');

    // Messages
    Route::get('/classlist/{classlist}/messages', [ClassMessageController::class, 'index'])->name('messages.index');
    Route::post('/classlist/{classlist}/messages/{student}', [ClassMessageController::class, 'storeForStudent'])->name('messages.store.student');

    // Calendar
    Route::get('/calendar', [\App\Http\Controllers\CalendarController::class, 'indexInstructor'])->name('calendar.index');
    Route::get('/calendar/export', [\App\Http\Controllers\CalendarController::class, 'exportIcal'])->name('calendar.export');

    // Assignment routes (index removed - unified in Activities)
    Route::get('/classlist/{classlist}/assignments/create', [AssignmentController::class, 'create'])->name('assignments.create');
    Route::post('/classlist/{classlist}/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::get('/classlist/{classlist}/assignments/{assignment}/edit', [AssignmentController::class, 'edit'])->name('assignments.edit');
    Route::get('/classlist/{classlist}/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
    Route::put('/classlist/{classlist}/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('/classlist/{classlist}/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

    // Assignment Grading
    Route::get('/classlist/{classlist}/assignments/{assignment}/grading', [\App\Http\Controllers\AssignmentGradingController::class, 'index'])->name('assignments.grading');
    Route::post('/classlist/{classlist}/assignments/{assignment}/submissions/{submission}/grade', [\App\Http\Controllers\AssignmentGradingController::class, 'grade'])->name('assignments.submissions.grade');
    Route::post('/classlist/{classlist}/assignments/{assignment}/bulk-grade', [\App\Http\Controllers\AssignmentGradingController::class, 'bulkGrade'])->name('assignments.bulk-grade');
    Route::post('/classlist/{classlist}/assignments/{assignment}/submissions/{submission}/annotations', [\App\Http\Controllers\AssignmentGradingController::class, 'addAnnotation'])->name('assignments.submissions.annotations.add');
    Route::delete('/classlist/{classlist}/assignments/{assignment}/submissions/{submission}/annotations', [\App\Http\Controllers\AssignmentGradingController::class, 'removeAnnotation'])->name('assignments.submissions.annotations.remove');

    Route::get('/classlist/{classlist}/activities', [ActivityController::class, 'index'])
        ->name('activities.index');
    Route::post('/classlist/{classlist}/activities', [ActivityController::class, 'store'])
        ->name('activities.store');
    Route::put('/classlist/{classlist}/activities/{activity}', [ActivityController::class, 'update'])
        ->name('activities.update');
    Route::delete('/classlist/{classlist}/activities/{activity}', [ActivityController::class, 'destroy'])
        ->name('activities.destroy');

    //Criteria routes
        //Criteria routes
    Route::get('/criteria', [CriteriaController::class, 'index'])->name('criteria.index');
    Route::post('/criteria', [CriteriaController::class, 'store'])->name('criteria.store');
    Route::put('/criteria/{criteria}', [CriteriaController::class, 'update'])->name('criteria.update');
    Route::delete('/criteria/{criteria}', [CriteriaController::class, 'destroy'])->name('criteria.destroy');

    // JSON for Activity Create select
    Route::get('/criteria/options', [CriteriaController::class, 'options'])->name('criteria.options');


    // Class Record (Gradebook)
    Route::get('/classlist/{classlist}/class-record', [\App\Http\Controllers\ClassRecordController::class, 'index'])
        ->name('class-record.index');
    Route::post('/classlist/{classlist}/class-record/components', [\App\Http\Controllers\ClassRecordController::class, 'storeComponent'])
        ->name('class-record.components.store');
    Route::put('/class-record/components/{component}', [\App\Http\Controllers\ClassRecordController::class, 'updateComponent'])
        ->name('class-record.components.update');
    Route::delete('/class-record/components/{component}', [\App\Http\Controllers\ClassRecordController::class, 'destroyComponent'])
        ->name('class-record.components.destroy');
    Route::post('/class-record/components/{component}/items', [\App\Http\Controllers\ClassRecordController::class, 'storeItem'])
        ->name('class-record.items.store');
    Route::put('/class-record/items/{item}', [\App\Http\Controllers\ClassRecordController::class, 'updateItem'])
        ->name('class-record.items.update');
    Route::delete('/class-record/items/{item}', [\App\Http\Controllers\ClassRecordController::class, 'destroyItem'])
        ->name('class-record.items.destroy');
    Route::post('/class-record/items/{item}/grades', [\App\Http\Controllers\ClassRecordController::class, 'updateGrades'])
        ->name('class-record.grades.update');
    Route::get('/classlist/{classlist}/class-record/export', [\App\Http\Controllers\ClassRecordController::class, 'export'])
        ->name('class-record.export');

    // Analytics
    Route::get('/analytics', [InstructorAnalyticsController::class, 'index'])->name('analytics');
    Route::get('/analytics/data', [InstructorAnalyticsController::class, 'data'])->name('analytics.data');

    // Student Progress
    Route::get('/progress', [StudentProgressController::class, 'index'])->name('progress');
    Route::get('/progress/{student}', [StudentProgressController::class, 'show'])->name('progress.show');

    // Performance Reports
    Route::get('/reports', [PerformanceReportController::class, 'index'])->name('reports');
    Route::get('/reports/generate', [PerformanceReportController::class, 'generate'])->name('reports.generate');
    Route::get('/reports/download/{report}', [PerformanceReportController::class, 'download'])->name('reports.download');

    // Comments
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::put('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/notifications/preferences', [\App\Http\Controllers\NotificationController::class, 'preferences'])->name('notifications.preferences');
    Route::put('/notifications/preferences', [\App\Http\Controllers\NotificationController::class, 'updatePreferences'])->name('notifications.preferences.update');

    // Quiz routes (index removed - unified in Activities)
    Route::get('/classlist/{classlist}/quizzes/create', [QuizController::class, 'create'])->name('quizzes.create');
    Route::post('/classlist/{classlist}/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
    Route::get('/classlist/{classlist}/quizzes/{quiz}/edit', [QuizController::class, 'edit'])->name('quizzes.edit');
    Route::get('/classlist/{classlist}/quizzes/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
    Route::put('/classlist/{classlist}/quizzes/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
    Route::delete('/classlist/{classlist}/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');

    // Examination routes (index removed - unified in Activities)
    Route::get('/classlist/{classlist}/examinations/create', [ExaminationController::class, 'create'])->name('examinations.create');
    Route::post('/classlist/{classlist}/examinations', [ExaminationController::class, 'store'])->name('examinations.store');
    Route::get('/classlist/{classlist}/examinations/{examination}/edit', [ExaminationController::class, 'edit'])->name('examinations.edit');
    Route::get('/classlist/{classlist}/examinations/{examination}', [ExaminationController::class, 'show'])->name('examinations.show');
    Route::put('/classlist/{classlist}/examinations/{examination}', [ExaminationController::class, 'update'])->name('examinations.update');
    Route::delete('/classlist/{classlist}/examinations/{examination}', [ExaminationController::class, 'destroy'])->name('examinations.destroy');

    // Material routes (index removed - unified in Activities)
    Route::get('/classlist/{classlist}/materials/create', [MaterialController::class, 'create'])->name('materials.create');
    Route::post('/classlist/{classlist}/materials', [MaterialController::class, 'store'])->name('materials.store');
    Route::get('/classlist/{classlist}/materials/{material}/edit', [MaterialController::class, 'edit'])->name('materials.edit');
    Route::get('/classlist/{classlist}/materials/{material}', [MaterialController::class, 'show'])->name('materials.show');
    Route::put('/classlist/{classlist}/materials/{material}', [MaterialController::class, 'update'])->name('materials.update');
    Route::delete('/classlist/{classlist}/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');
});


// Student routes
Route::middleware([StudentMiddleware::class])->prefix('student')->name('student.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

    // Grades
    Route::get('/grades', [\App\Http\Controllers\StudentGradesController::class, 'index'])->name('grades.index');

    // Calendar
    Route::get('/calendar', [\App\Http\Controllers\CalendarController::class, 'indexStudent'])->name('calendar.index');
    Route::get('/calendar/export', [\App\Http\Controllers\CalendarController::class, 'exportIcal'])->name('calendar.export');

    Route::get('/classlist', [ClasslistUserController::class, 'index'])->name('classlist');
    Route::post('/class-join', [ClasslistUserController::class, 'join'])->name('class.join');
    Route::get('/class/{id}', [ClasslistUserController::class, 'show'])->name('class.show');
    Route::post('/class-unenroll/{id}', [ClasslistUserController::class, 'unenroll'])->name('class.unenroll');
    Route::post('/class-archive/{id}', [ClasslistUserController::class, 'archive'])->name('class.archive');
    Route::post('/class-restore/{id}', [ClasslistUserController::class, 'restore'])->name('class.restore');

    // Archived Classes
    Route::get('/archived-classes', [\App\Http\Controllers\Student\ArchivedClassesController::class, 'index'])->name('archived-classes.index');
    Route::post('/archived-classes/{id}/restore', [\App\Http\Controllers\Student\ArchivedClassesController::class, 'restore'])->name('archived-classes.restore');

    Route::get('/classlist/{classlist}/activities', [StudentActivityController::class, 'index'])
        ->name('activities.index');

    // Submissions
    Route::get('/classlist/{classlist}/submissions', [\App\Http\Controllers\Student\SubmissionHistoryController::class, 'index'])
        ->name('submissions.index');

    // Attendance routes
    Route::get('/classlist/{classlist}/attendance', [\App\Http\Controllers\StudentAttendanceController::class, 'index'])->name('attendance.index');

    // Messages
    Route::get('/classlist/{classlist}/messages', [ClassMessageController::class, 'index'])->name('messages.index');
    Route::post('/classlist/{classlist}/messages', [ClassMessageController::class, 'store'])->name('messages.store');

    // Activity details
    Route::get('/classlist/{classlist}/activities/{activity}', [StudentActivityController::class, 'show'])
        ->name('activities.show');

    Route::get('/classlist/{classlist}/activities/{activity}/answer', [ActivitySubmissionController::class, 'show'])
        ->name('activities.answer');

    Route::post('/classlist/{classlist}/activities/{activity}/answer/save', [ActivitySubmissionController::class, 'saveDraft'])
        ->name('activities.answer.save');

    Route::post('/classlist/{classlist}/activities/{activity}/answer/submit', [ActivitySubmissionController::class, 'submit'])
        ->name('activities.answer.submit');

    Route::post('/classlist/{classlist}/activities/{activity}/answer/run', [StudentRunController::class, 'run'])
        ->name('activities.answer.run');

        Route::post('/classlist/{classlist}/activities/{activity}/grade', [StudentGradeController::class, 'grade'])
        ->name('activities.grade');

    Route::get('/classlist/{classlist}/activities/{activity}/evaluation', [StudentGradeController::class, 'latest'])
        ->name('activities.evaluation');

    // Quiz routes
    Route::get('/classlist/{classlist}/quizzes', [StudentQuizController::class, 'index'])->name('quizzes.index');
    Route::get('/classlist/{classlist}/quizzes/{quiz}', [StudentQuizController::class, 'show'])->name('quizzes.show');
    Route::post('/classlist/{classlist}/quizzes/{quiz}/start', [StudentQuizController::class, 'start'])->name('quizzes.start');
    Route::get('/classlist/{classlist}/quizzes/{quiz}/attempt/{attempt}', [StudentQuizController::class, 'take'])->name('quizzes.take');
    Route::post('/classlist/{classlist}/quizzes/{quiz}/attempt/{attempt}/answer', [StudentQuizController::class, 'saveAnswer'])->name('quizzes.saveAnswer');
    Route::post('/classlist/{classlist}/quizzes/{quiz}/attempt/{attempt}/submit', [StudentQuizController::class, 'submit'])->name('quizzes.submit');
    Route::get('/classlist/{classlist}/quizzes/{quiz}/attempt/{attempt}/result', [StudentQuizController::class, 'result'])->name('quizzes.result');

    // Assignment routes
    Route::get('/classlist/{classlist}/assignments', [StudentAssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/classlist/{classlist}/assignments/{assignment}', [StudentAssignmentController::class, 'show'])->name('assignments.show');
    Route::post('/classlist/{classlist}/assignments/{assignment}/submit', [\App\Http\Controllers\StudentAssignmentSubmissionController::class, 'store'])->name('assignments.submit');
    Route::put('/classlist/{classlist}/assignments/{assignment}/submissions/{submission}', [\App\Http\Controllers\StudentAssignmentSubmissionController::class, 'update'])->name('assignments.submissions.update');

    // Comments
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::put('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/notifications/preferences', [\App\Http\Controllers\NotificationController::class, 'preferences'])->name('notifications.preferences');
    Route::put('/notifications/preferences', [\App\Http\Controllers\NotificationController::class, 'updatePreferences'])->name('notifications.preferences.update');

    // Examination routes
    Route::get('/classlist/{classlist}/examinations', [StudentExaminationController::class, 'index'])->name('examinations.index');
    Route::get('/classlist/{classlist}/examinations/{examination}', [StudentExaminationController::class, 'show'])->name('examinations.show');
    Route::post('/classlist/{classlist}/examinations/{examination}/start', [StudentExaminationController::class, 'start'])->name('examinations.start');
    Route::get('/classlist/{classlist}/examinations/{examination}/attempt/{attempt}', [StudentExaminationController::class, 'take'])->name('examinations.take');
    Route::post('/classlist/{classlist}/examinations/{examination}/attempt/{attempt}/answer', [StudentExaminationController::class, 'saveAnswer'])->name('examinations.saveAnswer');
    Route::post('/classlist/{classlist}/examinations/{examination}/attempt/{attempt}/submit', [StudentExaminationController::class, 'submit'])->name('examinations.submit');
    Route::get('/classlist/{classlist}/examinations/{examination}/attempt/{attempt}/result', [StudentExaminationController::class, 'result'])->name('examinations.result');

    // Material routes
    Route::get('/classlist/{classlist}/materials', [StudentMaterialController::class, 'index'])->name('materials.index');
    Route::get('/classlist/{classlist}/materials/{material}', [StudentMaterialController::class, 'show'])->name('materials.show');
    Route::get('/classlist/{classlist}/materials/{material}/attachments/{attachment}/download', [StudentMaterialController::class, 'downloadAttachment'])->name('materials.attachments.download');

    // Attempt activity logging (anti-cheating)
    Route::post('/attempt-activities/log', [\App\Http\Controllers\AttemptActivityController::class, 'log'])->name('attempt-activities.log');

    // File Management
    Route::prefix('files')->name('files.')->group(function () {
        // Folders
        Route::get('/folders', [\App\Http\Controllers\FileFolderController::class, 'index'])->name('folders.index');
        Route::post('/folders', [\App\Http\Controllers\FileFolderController::class, 'store'])->name('folders.store');
        Route::put('/folders/{folder}', [\App\Http\Controllers\FileFolderController::class, 'update'])->name('folders.update');
        Route::delete('/folders/{folder}', [\App\Http\Controllers\FileFolderController::class, 'destroy'])->name('folders.destroy');
        Route::post('/folders/move-file', [\App\Http\Controllers\FileFolderController::class, 'moveFile'])->name('folders.move-file');

        // Versions
        Route::get('/attachments/{attachment}/versions', [\App\Http\Controllers\FileVersionController::class, 'index'])->name('versions.index');
        Route::post('/attachments/{attachment}/versions', [\App\Http\Controllers\FileVersionController::class, 'store'])->name('versions.store');
        Route::post('/attachments/{attachment}/versions/restore', [\App\Http\Controllers\FileVersionController::class, 'restore'])->name('versions.restore');
    });

});

// Admin routes
Route::middleware([AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users' => \App\Models\User::count(),
                'total_students' => \App\Models\User::where('account_type', 'student')->count(),
                'total_instructors' => \App\Models\User::where('account_type', 'instructor')->count(),
                'total_classes' => \App\Models\Classlist::count(),
                'total_academic_years' => \App\Models\AcademicYear::count(),
            ],
        ]);
    })->name('dashboard');

    // User Management
    Route::resource('users', \App\Http\Controllers\Admin\AdminUserController::class);

    // Academic Year Management
    Route::get('/academic-years', [\App\Http\Controllers\Admin\AdminAcademicYearController::class, 'index'])->name('academic-years.index');
    Route::post('/academic-years', [\App\Http\Controllers\Admin\AdminAcademicYearController::class, 'store'])->name('academic-years.store');
    Route::put('/academic-years/{academicYear}', [\App\Http\Controllers\Admin\AdminAcademicYearController::class, 'update'])->name('academic-years.update');
    Route::delete('/academic-years/{academicYear}', [\App\Http\Controllers\Admin\AdminAcademicYearController::class, 'destroy'])->name('academic-years.destroy');

});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
