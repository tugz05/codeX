<?php

namespace App\Http\Controllers;

use App\Models\Classlist;
use App\Models\QuizAttempt;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LandingPageController extends Controller
{
    public function index()
    {
        // Get top students by classlist (subject) performance
        $leaderboard = $this->getLeaderboard();

        return Inertia::render('Landing', [
            'leaderboard' => $leaderboard,
        ]);
    }

    private function getLeaderboard()
    {
        // Get all classlists with their top performers
        $classlists = Classlist::whereHas('students')
            ->get();

        $leaderboard = [];

        foreach ($classlists as $classlist) {
            // Get quiz attempts for this classlist
            $quizAttempts = QuizAttempt::where('classlist_id', $classlist->id)
                ->where('status', 'submitted')
                ->select('user_id', DB::raw('AVG(percentage) as avg_percentage'), DB::raw('COUNT(*) as total_attempts'))
                ->groupBy('user_id')
                ->having('total_attempts', '>', 0)
                ->orderByDesc('avg_percentage')
                ->limit(5)
                ->get();

            // Get exam attempts for this classlist
            $examAttempts = ExamAttempt::where('classlist_id', $classlist->id)
                ->where('status', 'submitted')
                ->select('user_id', DB::raw('AVG(percentage) as avg_percentage'), DB::raw('COUNT(*) as total_attempts'))
                ->groupBy('user_id')
                ->having('total_attempts', '>', 0)
                ->orderByDesc('avg_percentage')
                ->limit(5)
                ->get();

            // Combine and calculate overall performance
            $studentScores = [];

            // Process quiz attempts
            foreach ($quizAttempts as $attempt) {
                if (!isset($studentScores[$attempt->user_id])) {
                    $studentScores[$attempt->user_id] = [
                        'total_percentage' => 0,
                        'total_attempts' => 0,
                    ];
                }
                $studentScores[$attempt->user_id]['total_percentage'] += $attempt->avg_percentage * $attempt->total_attempts;
                $studentScores[$attempt->user_id]['total_attempts'] += $attempt->total_attempts;
            }

            // Process exam attempts
            foreach ($examAttempts as $attempt) {
                if (!isset($studentScores[$attempt->user_id])) {
                    $studentScores[$attempt->user_id] = [
                        'total_percentage' => 0,
                        'total_attempts' => 0,
                    ];
                }
                $studentScores[$attempt->user_id]['total_percentage'] += $attempt->avg_percentage * $attempt->total_attempts;
                $studentScores[$attempt->user_id]['total_attempts'] += $attempt->total_attempts;
            }

            // Calculate weighted average and get top 5
            $topStudents = [];
            foreach ($studentScores as $userId => $data) {
                if ($data['total_attempts'] > 0) {
                    $avgPercentage = $data['total_percentage'] / $data['total_attempts'];
                    $topStudents[] = [
                        'user_id' => $userId,
                        'average_percentage' => round($avgPercentage, 2),
                        'total_attempts' => $data['total_attempts'],
                    ];
                }
            }

            // Sort by average percentage descending
            usort($topStudents, function ($a, $b) {
                return $b['average_percentage'] <=> $a['average_percentage'];
            });

            // Get top 5 and load user details
            $topStudents = array_slice($topStudents, 0, 5);
            
            foreach ($topStudents as &$student) {
                $user = User::find($student['user_id']);
                if ($user) {
                    $student['name'] = $user->name;
                    $student['email'] = $user->email;
                }
            }

            if (count($topStudents) > 0) {
                $leaderboard[] = [
                    'classlist' => [
                        'id' => $classlist->id,
                        'name' => $classlist->name,
                        'section' => $classlist->section,
                    ],
                    'top_students' => $topStudents,
                ];
            }
        }

        return $leaderboard;
    }
}
