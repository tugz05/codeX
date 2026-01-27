<?php

namespace App\Http\Controllers;

use App\Models\Classlist;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QuizController extends Controller
{
    public function create(Classlist $classlist)
    {
        return Inertia::render('Instructor/Quizzes/Create', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'other_classlists' => Classlist::where('user_id', Auth::id())
                ->where('id', '!=', $classlist->id)
                ->orderBy('name')
                ->get(['id', 'name', 'room', 'section', 'academic_year']),
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
            'also_classlist_ids' => ['array'],
            'also_classlist_ids.*' => ['integer', 'exists:classlists,id'],
        ]);

        DB::beginTransaction();
        try {
            $auth = Auth::id();
            $targetIds = collect($data['also_classlist_ids'] ?? [])
                ->push($classlist->id)
                ->unique()
                ->values();

            $targetClasslists = Classlist::where('user_id', $auth)
                ->whereIn('id', $targetIds)
                ->get();

            abort_unless($targetClasslists->count() === $targetIds->count(), 403);

            foreach ($targetClasslists as $targetClasslist) {
                $quiz = Quiz::create([
                    'classlist_id' => $targetClasslist->id,
                    'created_by' => $auth,
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'total_points' => 0,
                    'time_limit' => $data['time_limit'] ?? null,
                    'attempts_allowed' => $data['attempts_allowed'],
                    'shuffle_questions' => $data['shuffle_questions'] ?? false,
                    'show_correct_answers' => $data['show_correct_answers'] ?? true,
                    'is_published' => $data['is_published'] ?? false,
                    'start_date' => $data['start_date'] ? date('Y-m-d H:i:s', strtotime($data['start_date'])) : null,
                    'end_date' => $data['end_date'] ? date('Y-m-d H:i:s', strtotime($data['end_date'])) : null,
                ]);

                $totalPoints = 0;

                foreach ($data['tests'] as $testIndex => $testData) {
                    $testPoints = collect($testData['questions'])->sum('points');
                    $totalPoints += $testPoints;

                    $test = \App\Models\Test::create([
                        'testable_id' => $quiz->id,
                        'testable_type' => Quiz::class,
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
                            'questionable_id' => $quiz->id,
                            'questionable_type' => Quiz::class,
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

                $quiz->update(['total_points' => $totalPoints]);

                if ($quiz->is_published) {
                    $notificationService = app(NotificationService::class);
                    $students = $targetClasslist->students()->where('status', 'active')->get();
                    foreach ($students as $student) {
                        $actionUrl = route('student.quizzes.show', [$targetClasslist->id, $quiz->id], false);
                        $message = "A new quiz '{$quiz->title}' is now available in {$targetClasslist->name}.";

                        $notificationService->sendNotification(
                            'quiz_created',
                            [$student],
                            $quiz->title,
                            $message,
                            Quiz::class,
                            $quiz->id,
                            $targetClasslist->id,
                            $actionUrl
                        );

                        $notificationService->sendEmailNotification(
                            'quiz_created',
                            $student,
                            $quiz->title,
                            $message,
                            url($actionUrl)
                        );
                    }
                }
            }

            DB::commit();
            return back()->with('success', 'Quiz created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create quiz: ' . $e->getMessage()]);
        }
    }

    public function edit(Classlist $classlist, Quiz $quiz)
    {
        abort_unless($quiz->classlist_id === $classlist->id, 404);
        
        $quiz->load(['tests.questions']);
        
        $tests = $quiz->tests->map(function($test) {
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

        return Inertia::render('Instructor/Quizzes/Edit', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'time_limit' => $quiz->time_limit,
                'attempts_allowed' => $quiz->attempts_allowed,
                'shuffle_questions' => $quiz->shuffle_questions,
                'show_correct_answers' => $quiz->show_correct_answers,
                'is_published' => $quiz->is_published,
                'start_date' => $quiz->start_date ? $quiz->start_date->format('Y-m-d\TH:i') : null,
                'end_date' => $quiz->end_date ? $quiz->end_date->format('Y-m-d\TH:i') : null,
                'tests' => $tests,
            ],
        ]);
    }

    public function update(Request $request, Classlist $classlist, Quiz $quiz)
    {
        abort_unless($quiz->classlist_id === $classlist->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'time_limit' => ['nullable', 'integer', 'min:1'],
            'attempts_allowed' => ['required', 'integer', 'min:1', 'max:10'],
            'shuffle_questions' => ['boolean'],
            'show_correct_answers' => ['boolean'],
            'is_published' => ['boolean'],
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
            $wasPublished = $quiz->is_published;
            
            $quiz->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'time_limit' => $data['time_limit'] ?? null,
                'attempts_allowed' => $data['attempts_allowed'],
                'shuffle_questions' => $data['shuffle_questions'] ?? false,
                'show_correct_answers' => $data['show_correct_answers'] ?? true,
                'is_published' => $data['is_published'] ?? false,
                'start_date' => $data['start_date'] ? date('Y-m-d H:i:s', strtotime($data['start_date'])) : null,
                'end_date' => $data['end_date'] ? date('Y-m-d H:i:s', strtotime($data['end_date'])) : null,
            ]);

            // Delete existing tests and questions
            $quiz->tests()->delete();

            $totalPoints = 0;

            foreach ($data['tests'] as $testIndex => $testData) {
                $testPoints = collect($testData['questions'])->sum('points');
                $totalPoints += $testPoints;

                $test = \App\Models\Test::create([
                    'testable_id' => $quiz->id,
                    'testable_type' => Quiz::class,
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
                        'questionable_id' => $quiz->id,
                        'questionable_type' => Quiz::class,
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

            $quiz->update(['total_points' => $totalPoints]);

            // Send notifications if quiz was just published
            if (!$wasPublished && $quiz->is_published) {
                $notificationService = app(NotificationService::class);
                $students = $classlist->students()->where('status', 'active')->get();
                
                foreach ($students as $student) {
                    $actionUrl = route('student.quizzes.show', [$classlist->id, $quiz->id], false);
                    $message = "A new quiz '{$quiz->title}' is now available in {$classlist->name}.";
                    
                    // In-app notification
                    $notificationService->sendNotification(
                        'quiz_created',
                        [$student],
                        $quiz->title,
                        $message,
                        Quiz::class,
                        $quiz->id,
                        $classlist->id,
                        $actionUrl
                    );
                    
                    // Email notification
                    $notificationService->sendEmailNotification(
                        'quiz_created',
                        $student,
                        $quiz->title,
                        $message,
                        url($actionUrl)
                    );
                }
            }

            DB::commit();
            return back()->with('success', 'Quiz updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update quiz: ' . $e->getMessage()]);
        }
    }

    public function destroy(Classlist $classlist, Quiz $quiz)
    {
        abort_unless($quiz->classlist_id === $classlist->id, 404);
        $quiz->delete();
        return back()->with('success', 'Quiz deleted successfully.');
    }

    public function show(Classlist $classlist, Quiz $quiz)
    {
        abort_unless($quiz->classlist_id === $classlist->id, 404);
        
        $quiz->load(['tests.questions']);
        
        $tests = $quiz->tests->map(function($test) {
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
        $attempts = $quiz->attempts()->with('user')->get();
        $totalAttempts = $attempts->count();
        $uniqueUsers = $attempts->pluck('user_id')->unique()->count();
        $averageScore = $attempts->where('score', '!=', null)->avg('score') ?? 0;
        
        return Inertia::render('Instructor/Quizzes/Show', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'total_points' => $quiz->total_points,
                'time_limit' => $quiz->time_limit,
                'attempts_allowed' => $quiz->attempts_allowed,
                'shuffle_questions' => $quiz->shuffle_questions,
                'show_correct_answers' => $quiz->show_correct_answers,
                'is_published' => $quiz->is_published,
                'start_date' => $quiz->start_date,
                'end_date' => $quiz->end_date,
                'created_at' => $quiz->created_at,
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
