<?php

namespace App\Http\Controllers;

use App\Models\Classlist;
use App\Models\Examination;
use App\Models\Question;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ExaminationController extends Controller
{
    public function create(Classlist $classlist)
    {
        return Inertia::render('Instructor/Examinations/Create', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
        ]);
    }

    public function store(Request $request, Classlist $classlist)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'time_limit' => ['nullable', 'integer', 'min:1'],
            'attempts_allowed' => ['required', 'integer', 'min:1', 'max:10'],
            'shuffle_questions' => ['boolean'],
            'show_correct_answers' => ['boolean'],
            'is_published' => ['boolean'],
            'require_proctoring' => ['boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'tests' => ['required', 'array', 'min:1'],
            'tests.*.title' => ['required', 'string', 'max:200'],
            'tests.*.type' => ['nullable', 'string', 'max:100'],
            'tests.*.description' => ['nullable', 'string', 'max:1000'],
            'tests.*.questions' => ['required', 'array', 'min:1'],
            'tests.*.questions.*.question_text' => ['required', 'string'],
            'tests.*.questions.*.type' => ['required', 'in:multiple_choice,true_false,short_answer,essay'],
            'tests.*.questions.*.points' => ['required', 'integer', 'min:1'],
            'tests.*.questions.*.options' => ['nullable', 'array'],
            'tests.*.questions.*.correct_answer' => ['required'],
            'tests.*.questions.*.explanation' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            $examination = Examination::create([
                'classlist_id' => $classlist->id,
                'created_by' => Auth::id(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'total_points' => 0, // Will be calculated
                'time_limit' => $data['time_limit'] ?? null,
                'attempts_allowed' => $data['attempts_allowed'],
                'shuffle_questions' => $data['shuffle_questions'] ?? false,
                'show_correct_answers' => $data['show_correct_answers'] ?? false,
                'is_published' => $data['is_published'] ?? false,
                'require_proctoring' => $data['require_proctoring'] ?? false,
                'start_date' => $data['start_date'] ? date('Y-m-d H:i:s', strtotime($data['start_date'])) : null,
                'end_date' => $data['end_date'] ? date('Y-m-d H:i:s', strtotime($data['end_date'])) : null,
            ]);

            $totalPoints = 0;

            foreach ($data['tests'] as $testIndex => $testData) {
                $testPoints = collect($testData['questions'])->sum('points');
                $totalPoints += $testPoints;

                $test = \App\Models\Test::create([
                    'testable_id' => $examination->id,
                    'testable_type' => Examination::class,
                    'title' => $testData['title'],
                    'type' => $testData['type'] ?? null,
                    'description' => $testData['description'] ?? null,
                    'order' => $testIndex + 1,
                    'total_points' => $testPoints,
                ]);

                foreach ($testData['questions'] as $qIndex => $questionData) {
                    $correctAnswer = is_array($questionData['correct_answer']) 
                        ? $questionData['correct_answer'] 
                        : [$questionData['correct_answer']];

                    Question::create([
                        'test_id' => $test->id,
                        'questionable_id' => $examination->id,
                        'questionable_type' => Examination::class,
                        'question_text' => $questionData['question_text'],
                        'type' => $questionData['type'],
                        'points' => $questionData['points'],
                        'order' => $qIndex + 1,
                        'options' => $questionData['options'] ?? null,
                        'correct_answer' => $correctAnswer,
                        'explanation' => $questionData['explanation'] ?? null,
                        'is_active' => true,
                    ]);
                }
            }

            $examination->update(['total_points' => $totalPoints]);

            // Send notifications to enrolled students if published
            if ($examination->is_published) {
                $notificationService = app(NotificationService::class);
                $students = $classlist->students()->where('status', 'active')->get();
                
                foreach ($students as $student) {
                    $actionUrl = route('student.examinations.show', [$classlist->id, $examination->id], false);
                    $message = "A new examination '{$examination->title}' is now available in {$classlist->name}.";
                    
                    // In-app notification
                    $notificationService->sendNotification(
                        'examination_created',
                        [$student],
                        $examination->title,
                        $message,
                        Examination::class,
                        $examination->id,
                        $classlist->id,
                        $actionUrl
                    );
                    
                    // Email notification
                    $notificationService->sendEmailNotification(
                        'examination_created',
                        $student,
                        $examination->title,
                        $message,
                        url($actionUrl)
                    );
                }
            }

            DB::commit();
            return back()->with('success', 'Examination created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create examination: ' . $e->getMessage()]);
        }
    }

    public function edit(Classlist $classlist, Examination $examination)
    {
        abort_unless($examination->classlist_id === $classlist->id, 404);
        
        $examination->load(['tests.questions']);
        
        $tests = $examination->tests->map(function($test) {
            return [
                'id' => $test->id,
                'title' => $test->title,
                'type' => $test->type,
                'description' => $test->description,
                'order' => $test->order,
                'questions' => $test->questions->map(function($q) {
                    return [
                        'id' => $q->id,
                        'question_text' => $q->question_text,
                        'type' => $q->type,
                        'points' => $q->points,
                        'options' => $q->options ?? [],
                        'correct_answer' => is_array($q->correct_answer) ? $q->correct_answer[0] : $q->correct_answer,
                        'explanation' => $q->explanation,
                    ];
                })->sortBy('order')->values(),
            ];
        })->sortBy('order')->values();

        return Inertia::render('Instructor/Examinations/Edit', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'examination' => [
                'id' => $examination->id,
                'title' => $examination->title,
                'description' => $examination->description,
                'time_limit' => $examination->time_limit,
                'attempts_allowed' => $examination->attempts_allowed,
                'shuffle_questions' => $examination->shuffle_questions,
                'show_correct_answers' => $examination->show_correct_answers,
                'is_published' => $examination->is_published,
                'require_proctoring' => $examination->require_proctoring,
                'start_date' => $examination->start_date ? $examination->start_date->format('Y-m-d\TH:i') : null,
                'end_date' => $examination->end_date ? $examination->end_date->format('Y-m-d\TH:i') : null,
                'tests' => $tests,
            ],
        ]);
    }

    public function update(Request $request, Classlist $classlist, Examination $examination)
    {
        abort_unless($examination->classlist_id === $classlist->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'time_limit' => ['nullable', 'integer', 'min:1'],
            'attempts_allowed' => ['required', 'integer', 'min:1', 'max:10'],
            'shuffle_questions' => ['boolean'],
            'show_correct_answers' => ['boolean'],
            'is_published' => ['boolean'],
            'require_proctoring' => ['boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'tests' => ['required', 'array', 'min:1'],
            'tests.*.title' => ['required', 'string', 'max:200'],
            'tests.*.type' => ['nullable', 'string', 'max:100'],
            'tests.*.description' => ['nullable', 'string', 'max:1000'],
            'tests.*.questions' => ['required', 'array', 'min:1'],
            'tests.*.questions.*.question_text' => ['required', 'string'],
            'tests.*.questions.*.type' => ['required', 'in:multiple_choice,true_false,short_answer,essay'],
            'tests.*.questions.*.points' => ['required', 'integer', 'min:1'],
            'tests.*.questions.*.options' => ['nullable', 'array'],
            'tests.*.questions.*.correct_answer' => ['required'],
            'tests.*.questions.*.explanation' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            $wasPublished = $examination->is_published;
            
            $examination->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'time_limit' => $data['time_limit'] ?? null,
                'attempts_allowed' => $data['attempts_allowed'],
                'shuffle_questions' => $data['shuffle_questions'] ?? false,
                'show_correct_answers' => $data['show_correct_answers'] ?? false,
                'is_published' => $data['is_published'] ?? false,
                'require_proctoring' => $data['require_proctoring'] ?? false,
                'start_date' => $data['start_date'] ? date('Y-m-d H:i:s', strtotime($data['start_date'])) : null,
                'end_date' => $data['end_date'] ? date('Y-m-d H:i:s', strtotime($data['end_date'])) : null,
            ]);

            // Delete existing tests and questions
            $examination->tests()->delete();

            $totalPoints = 0;

            foreach ($data['tests'] as $testIndex => $testData) {
                $testPoints = collect($testData['questions'])->sum('points');
                $totalPoints += $testPoints;

                $test = \App\Models\Test::create([
                    'testable_id' => $examination->id,
                    'testable_type' => Examination::class,
                    'title' => $testData['title'],
                    'type' => $testData['type'] ?? null,
                    'description' => $testData['description'] ?? null,
                    'order' => $testIndex + 1,
                    'total_points' => $testPoints,
                ]);

                foreach ($testData['questions'] as $qIndex => $questionData) {
                    $correctAnswer = is_array($questionData['correct_answer']) 
                        ? $questionData['correct_answer'] 
                        : [$questionData['correct_answer']];

                    Question::create([
                        'test_id' => $test->id,
                        'questionable_id' => $examination->id,
                        'questionable_type' => Examination::class,
                        'question_text' => $questionData['question_text'],
                        'type' => $questionData['type'],
                        'points' => $questionData['points'],
                        'order' => $qIndex + 1,
                        'options' => $questionData['options'] ?? null,
                        'correct_answer' => $correctAnswer,
                        'explanation' => $questionData['explanation'] ?? null,
                        'is_active' => true,
                    ]);
                }
            }

            $examination->update(['total_points' => $totalPoints]);

            // Send notifications if examination was just published
            if (!$wasPublished && $examination->is_published) {
                $notificationService = app(NotificationService::class);
                $students = $classlist->students()->where('status', 'active')->get();
                
                foreach ($students as $student) {
                    $actionUrl = route('student.examinations.show', [$classlist->id, $examination->id], false);
                    $message = "A new examination '{$examination->title}' is now available in {$classlist->name}.";
                    
                    // In-app notification
                    $notificationService->sendNotification(
                        'examination_created',
                        [$student],
                        $examination->title,
                        $message,
                        Examination::class,
                        $examination->id,
                        $classlist->id,
                        $actionUrl
                    );
                    
                    // Email notification
                    $notificationService->sendEmailNotification(
                        'examination_created',
                        $student,
                        $examination->title,
                        $message,
                        url($actionUrl)
                    );
                }
            }

            DB::commit();
            return back()->with('success', 'Examination updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update examination: ' . $e->getMessage()]);
        }
    }

    public function destroy(Classlist $classlist, Examination $examination)
    {
        abort_unless($examination->classlist_id === $classlist->id, 404);
        $examination->delete();
        return back()->with('success', 'Examination deleted successfully.');
    }

    public function show(Classlist $classlist, Examination $examination)
    {
        abort_unless($examination->classlist_id === $classlist->id, 404);
        
        $examination->load(['tests.questions']);
        
        $tests = $examination->tests->map(function($test) {
            return [
                'id' => $test->id,
                'title' => $test->title,
                'type' => $test->type,
                'description' => $test->description,
                'order' => $test->order,
                'total_points' => $test->total_points,
                'questions' => $test->questions->map(function($q) {
                    return [
                        'id' => $q->id,
                        'question_text' => $q->question_text,
                        'type' => $q->type,
                        'points' => $q->points,
                        'options' => $q->options ?? [],
                        'correct_answer' => $q->correct_answer,
                        'explanation' => $q->explanation,
                        'order' => $q->order,
                    ];
                })->sortBy('order')->values(),
            ];
        })->sortBy('order')->values();

        // Get attempt statistics
        $attempts = $examination->attempts()->with('user')->get();
        $totalAttempts = $attempts->count();
        $uniqueUsers = $attempts->pluck('user_id')->unique()->count();
        $averageScore = $attempts->where('score', '!=', null)->avg('score') ?? 0;
        
        return Inertia::render('Instructor/Examinations/Show', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'examination' => [
                'id' => $examination->id,
                'title' => $examination->title,
                'description' => $examination->description,
                'total_points' => $examination->total_points,
                'time_limit' => $examination->time_limit,
                'attempts_allowed' => $examination->attempts_allowed,
                'shuffle_questions' => $examination->shuffle_questions,
                'show_correct_answers' => $examination->show_correct_answers,
                'is_published' => $examination->is_published,
                'require_proctoring' => $examination->require_proctoring,
                'start_date' => $examination->start_date,
                'end_date' => $examination->end_date,
                'created_at' => $examination->created_at,
                'tests' => $tests,
            ],
            'statistics' => [
                'total_attempts' => $totalAttempts,
                'unique_users' => $uniqueUsers,
                'average_score' => round($averageScore, 2),
            ],
        ]);
    }
}
