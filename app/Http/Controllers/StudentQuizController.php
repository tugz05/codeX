<?php

namespace App\Http\Controllers;

use App\Models\Classlist;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class StudentQuizController extends Controller
{
    public function index(Classlist $classlist)
    {
        // Ensure enrolled
        $enrolled = DB::table('classlist_user')
            ->where('classlist_id', $classlist->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->exists();
        abort_unless($enrolled, 403);

        $now = now();
        $quizzes = Quiz::where('classlist_id', $classlist->id)
            ->where('is_published', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $now);
            })
            ->latest()
            ->get();

        $userId = Auth::id();
        $attempts = QuizAttempt::where('classlist_id', $classlist->id)
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->unique('quiz_id')
            ->keyBy('quiz_id');

        $quizzes = $quizzes->map(function ($quiz) use ($attempts, $userId) {
            $userAttempts = $quiz->userAttempts($userId)->get();
            $latestAttempt = $userAttempts->sortByDesc('created_at')->first();
            $latestSubmittedAttempt = $userAttempts->where('status', 'submitted')->sortByDesc('submitted_at')->first();
            $canAttempt = $userAttempts->count() < $quiz->attempts_allowed;

            return [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'total_points' => $quiz->total_points,
                'time_limit' => $quiz->time_limit,
                'attempts_allowed' => $quiz->attempts_allowed,
                'attempts_count' => $userAttempts->count(),
                'can_attempt' => $canAttempt,
                'latest_score' => $latestAttempt?->score,
                'latest_percentage' => $latestAttempt?->percentage,
                'latest_status' => $latestAttempt?->status,
                'latest_attempt_id' => $latestSubmittedAttempt?->id,
                'start_date' => $quiz->start_date,
                'end_date' => $quiz->end_date,
            ];
        });

        return Inertia::render('Student/Quizzes/Index', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'quizzes' => $quizzes,
        ]);
    }

    public function show(Classlist $classlist, Quiz $quiz)
    {
        // Ensure enrolled
        $enrolled = DB::table('classlist_user')
            ->where('classlist_id', $classlist->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->exists();
        abort_unless($enrolled, 403);
        abort_unless($quiz->classlist_id === $classlist->id, 404);
        abort_unless($quiz->is_published, 404);

        $userId = Auth::id();
        $userAttempts = $quiz->userAttempts($userId)->get();
        $canAttempt = $userAttempts->count() < $quiz->attempts_allowed;

        return Inertia::render('Student/Quizzes/Show', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'total_points' => $quiz->total_points,
                'time_limit' => $quiz->time_limit,
                'attempts_allowed' => $quiz->attempts_allowed,
                'attempts_count' => $userAttempts->count(),
                'can_attempt' => $canAttempt,
                'show_correct_answers' => $quiz->show_correct_answers,
                'start_date' => $quiz->start_date,
                'end_date' => $quiz->end_date,
            ],
            'attempts' => $userAttempts->map(fn($a) => [
                'id' => $a->id,
                'attempt_number' => $a->attempt_number,
                'score' => $a->score,
                'percentage' => $a->percentage,
                'status' => $a->status,
                'submitted_at' => $a->submitted_at,
            ]),
        ]);
    }

    public function start(Classlist $classlist, Quiz $quiz)
    {
        $enrolled = DB::table('classlist_user')
            ->where('classlist_id', $classlist->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->exists();
        abort_unless($enrolled, 403);
        abort_unless($quiz->classlist_id === $classlist->id, 404);
        abort_unless($quiz->is_published, 404);

        $userId = Auth::id();
        $userAttempts = $quiz->userAttempts($userId)->get();
        
        if ($userAttempts->count() >= $quiz->attempts_allowed) {
            return back()->withErrors(['error' => 'Maximum attempts reached.']);
        }

        // Check if there's an in-progress attempt
        $inProgress = $userAttempts->where('status', 'in_progress')->first();
        if ($inProgress) {
            return redirect()->route('student.quizzes.take', [$classlist->id, $quiz->id, $inProgress->id]);
        }

        DB::beginTransaction();
        try {
            $attemptNumber = $userAttempts->count() + 1;
            
            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $userId,
                'classlist_id' => $classlist->id,
                'attempt_number' => $attemptNumber,
                'status' => 'in_progress',
                'started_at' => now(),
                'total_points' => $quiz->total_points,
            ]);

            DB::commit();
            return redirect()->route('student.quizzes.take', [$classlist->id, $quiz->id, $attempt->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to start quiz: ' . $e->getMessage()]);
        }
    }

    public function take(Classlist $classlist, Quiz $quiz, QuizAttempt $attempt)
    {
        abort_unless($attempt->quiz_id === $quiz->id, 404);
        abort_unless($attempt->user_id === Auth::id(), 403);
        abort_unless($attempt->status === 'in_progress', 403);

        $quiz->load(['tests.questions' => function($query) {
            $query->where('is_active', true)->orderBy('order');
        }]);

        $answers = $attempt->answers()->with('question')->get()->keyBy('question_id');

        // Build tests with questions
        $tests = $quiz->tests->map(function($test) use ($quiz, $answers) {
            $questions = $test->questions;
            
            // Shuffle questions within test if enabled
            if ($quiz->shuffle_questions) {
                $questions = $questions->shuffle();
            }

            return [
                'id' => $test->id,
                'title' => $test->title,
                'type' => $test->type,
                'description' => $test->description,
                'order' => $test->order,
                'questions' => $questions->map(function($q) use ($answers) {
                    return [
                        'id' => $q->id,
                        'test_id' => $q->test_id,
                        'question_text' => $q->question_text,
                        'type' => $q->type,
                        'points' => $q->points,
                        'options' => $q->options ?? [],
                        'order' => $q->order,
                    ];
                })->values(),
            ];
        })->sortBy('order')->values();

        // Flatten all questions for navigation
        $allQuestions = $tests->flatMap(function($test) {
            return $test['questions']->map(function($q) use ($test) {
                return array_merge($q, ['test_title' => $test['title'], 'test_id' => $test['id']]);
            });
        });

        return Inertia::render('Student/Quizzes/Take', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'time_limit' => $quiz->time_limit,
                'total_points' => $quiz->total_points,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'started_at' => $attempt->started_at,
            ],
            'tests' => $tests,
            'questions' => $allQuestions->values(),
            'answers' => $answers->map(fn($a) => [
                'question_id' => $a->question_id,
                'answer' => $a->answer,
            ]),
        ]);
    }

    public function saveAnswer(Request $request, Classlist $classlist, Quiz $quiz, QuizAttempt $attempt)
    {
        abort_unless($attempt->quiz_id === $quiz->id, 404);
        abort_unless($attempt->user_id === Auth::id(), 403);
        abort_unless($attempt->status === 'in_progress', 403);

        $data = $request->validate([
            'question_id' => ['required', 'exists:questions,id'],
            'answer' => ['required'],
        ]);

        $question = Question::findOrFail($data['question_id']);
        abort_unless($question->questionable_id === $quiz->id, 404);

        $answer = QuestionAnswer::updateOrCreate(
            [
                'question_id' => $data['question_id'],
                'attemptable_id' => $attempt->id,
                'attemptable_type' => QuizAttempt::class,
            ],
            [
                'answer' => is_array($data['answer']) ? $data['answer'] : [$data['answer']],
            ]
        );

        // Return back() for Inertia requests, or JSON for API requests
        if ($request->header('X-Inertia')) {
            return back();
        }
        
        return response()->json(['success' => true, 'answer_id' => $answer->id]);
    }

    public function submit(Request $request, Classlist $classlist, Quiz $quiz, QuizAttempt $attempt)
    {
        abort_unless($attempt->quiz_id === $quiz->id, 404);
        abort_unless($attempt->user_id === Auth::id(), 403);
        abort_unless($attempt->status === 'in_progress', 403);

        DB::beginTransaction();
        try {
            // Grade all answers
            $answers = $attempt->answers()->with('question')->get();
            foreach ($answers as $answer) {
                if ($answer->question) {
                    $answer->grade();
                    $answer->save();
                }
            }

            // Reload answers to ensure we have the latest data
            $attempt->load('answers.question');
            
            // Calculate score
            $attempt->calculateScore();
            
            // Calculate time spent (ensure it's positive)
            if ($attempt->started_at) {
                $timeSpent = now()->diffInSeconds($attempt->started_at);
                // Ensure time_spent is always positive (handle edge cases)
                $attempt->time_spent = max(0, abs($timeSpent));
            } else {
                $attempt->time_spent = 0;
            }
            
            // Save attempt before changing status to allow final activities to be logged
            $attempt->save();
            
            // Give a small delay to ensure any pending activities are logged
            // Activities logged via the composable should complete before status change
            usleep(100000); // 100ms delay
            
            // Now mark as submitted
            $attempt->status = 'submitted';
            $attempt->submitted_at = now();
            $attempt->save();

            DB::commit();
            
            // Redirect to results page - Inertia will handle this automatically
            return redirect()->route('student.quizzes.result', [$classlist->id, $quiz->id, $attempt->id])
                ->with('success', 'Quiz submitted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Quiz submission error: ' . $e->getMessage(), [
                'exception' => $e,
                'attempt_id' => $attempt->id,
                'quiz_id' => $quiz->id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return back()->withErrors([
                'error' => 'Failed to submit quiz: ' . $e->getMessage(),
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function result(Classlist $classlist, Quiz $quiz, QuizAttempt $attempt)
    {
        abort_unless($attempt->quiz_id === $quiz->id, 404);
        abort_unless($attempt->user_id === Auth::id(), 403);
        abort_unless($attempt->status === 'submitted', 403);

        $attempt->load(['answers.question', 'activities']);
        $quiz->load(['tests.questions' => function($query) {
            $query->where('is_active', true)->orderBy('order');
        }]);

        // Organize answers by test
        $tests = $quiz->tests->map(function($test) use ($attempt) {
            $questions = $test->questions->keyBy('id');
            $testAnswers = $attempt->answers->filter(function($answer) use ($questions) {
                return $questions->has($answer->question_id);
            });

            return [
                'id' => $test->id,
                'title' => $test->title,
                'type' => $test->type,
                'description' => $test->description,
                'order' => $test->order,
                'answers' => $testAnswers->map(function ($answer) use ($questions) {
                    $question = $questions->get($answer->question_id);
                    return [
                        'question_id' => $answer->question_id,
                        'question_text' => $question?->question_text,
                        'question_type' => $question?->type,
                        'question_points' => $question?->points,
                        'question_options' => $question?->options ?? [],
                        'correct_answer' => $question?->correct_answer,
                        'explanation' => $question?->explanation,
                        'user_answer' => $answer->answer,
                        'is_correct' => $answer->is_correct,
                        'points_earned' => $answer->points_earned,
                    ];
                })->sortBy(function($a) use ($questions) {
                    return $questions->get($a['question_id'])?->order ?? 0;
                })->values(),
            ];
        })->sortBy('order')->values();

        return Inertia::render('Student/Quizzes/Result', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'show_correct_answers' => $quiz->show_correct_answers,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'score' => $attempt->score,
                'total_points' => $attempt->total_points,
                'percentage' => $attempt->percentage,
                'submitted_at' => $attempt->submitted_at,
                'time_spent' => $attempt->time_spent,
            ],
            'tests' => $tests,
            'activities' => $attempt->activities->map(fn($a) => [
                'id' => $a->id,
                'activity_type' => $a->activity_type,
                'description' => $a->description,
                'metadata' => $a->metadata,
                'occurred_at' => $a->occurred_at->toIso8601String(),
            ])->sortBy('occurred_at')->values(),
        ]);
    }
}
