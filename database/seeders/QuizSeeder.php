<?php

namespace Database\Seeders;

use App\Models\Classlist;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create an instructor user
        $instructor = User::where('email', 'test@example.com')->first();

        if (!$instructor) {
            $instructor = User::factory()->create([
                'name' => 'Test Instructor',
                'email' => 'test@example.com',
                'account_type' => 'instructor',
            ]);
        }

        // Get or create a classlist
        $classlist = Classlist::first();

        if (!$classlist) {
            $classlist = Classlist::create([
                'user_id' => $instructor->id,
                'name' => 'Sample Class',
                'room' => 'Room 101',
                'academic_year' => '2024-2025',
                'section' => 'Sample Section',
            ]);
        }

        // Create a quiz
        $quiz = Quiz::create([
            'classlist_id' => $classlist->id,
            'created_by' => $instructor->id,
            'title' => 'Introduction to Programming Quiz',
            'description' => 'A comprehensive quiz covering basic programming concepts including variables, data types, control structures, and functions.',
            'total_points' => 50,
            'time_limit' => 30, // 30 minutes
            'attempts_allowed' => 3,
            'shuffle_questions' => false,
            'show_correct_answers' => true,
            'is_published' => true,
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(7),
        ]);

        // Test I - Multiple Choice Questions
        $test1 = Test::create([
            'testable_id' => $quiz->id,
            'testable_type' => Quiz::class,
            'title' => 'Test I - Multiple Choice',
            'type' => 'multiple_choice',
            'description' => 'Choose the best answer for each question.',
            'order' => 1,
        ]);

        // Question 1
        Question::create([
            'test_id' => $test1->id,
            'questionable_id' => $quiz->id,
            'questionable_type' => Quiz::class,
            'question_text' => 'What is a variable in programming?',
            'type' => 'multiple_choice',
            'points' => 10,
            'order' => 1,
            'options' => [
                'A container that stores data values',
                'A function that performs calculations',
                'A loop that repeats code',
                'A condition that checks values',
            ],
            'correct_answer' => ['A container that stores data values'],
            'explanation' => 'A variable is a named container that stores data values that can be used and modified throughout the program.',
            'is_active' => true,
        ]);

        // Question 2
        Question::create([
            'test_id' => $test1->id,
            'questionable_id' => $quiz->id,
            'questionable_type' => Quiz::class,
            'question_text' => 'Which data type is used to store whole numbers?',
            'type' => 'multiple_choice',
            'points' => 10,
            'order' => 2,
            'options' => [
                'String',
                'Integer',
                'Boolean',
                'Float',
            ],
            'correct_answer' => ['Integer'],
            'explanation' => 'Integer is the data type used to store whole numbers (positive, negative, or zero) without decimal points.',
            'is_active' => true,
        ]);

        // Test II - True/False Questions
        $test2 = Test::create([
            'testable_id' => $quiz->id,
            'testable_type' => Quiz::class,
            'title' => 'Test II - True or False',
            'type' => 'true_false',
            'description' => 'Determine if each statement is true or false.',
            'order' => 2,
        ]);

        // Question 3
        Question::create([
            'test_id' => $test2->id,
            'questionable_id' => $quiz->id,
            'questionable_type' => Quiz::class,
            'question_text' => 'A for loop is used to execute a block of code a specific number of times.',
            'type' => 'true_false',
            'points' => 10,
            'order' => 1,
            'options' => ['True', 'False'],
            'correct_answer' => ['True'],
            'explanation' => 'A for loop is indeed used to execute a block of code a specific number of times, making it ideal for iterating over arrays or performing repetitive tasks.',
            'is_active' => true,
        ]);

        // Question 4
        Question::create([
            'test_id' => $test2->id,
            'questionable_id' => $quiz->id,
            'questionable_type' => Quiz::class,
            'question_text' => 'All programming languages use the same syntax.',
            'type' => 'true_false',
            'points' => 10,
            'order' => 2,
            'options' => ['True', 'False'],
            'correct_answer' => ['False'],
            'explanation' => 'Different programming languages have different syntaxes. For example, Python uses indentation while JavaScript uses curly braces.',
            'is_active' => true,
        ]);

        // Test III - Short Answer/Essay Questions
        $test3 = Test::create([
            'testable_id' => $quiz->id,
            'testable_type' => Quiz::class,
            'title' => 'Test III - Short Answer',
            'type' => 'short_answer',
            'description' => 'Provide brief answers to the following questions.',
            'order' => 3,
        ]);

        // Question 5
        Question::create([
            'test_id' => $test3->id,
            'questionable_id' => $quiz->id,
            'questionable_type' => Quiz::class,
            'question_text' => 'Explain what a function is in programming and provide one example of its use.',
            'type' => 'short_answer',
            'points' => 10,
            'order' => 1,
            'options' => [],
            'correct_answer' => [
                'function',
                'reusable code',
                'block of code',
                'performs a task',
            ],
            'explanation' => 'A function is a reusable block of code that performs a specific task. It helps in organizing code, reducing repetition, and making programs more modular. Example: A function to calculate the area of a rectangle.',
            'is_active' => true,
        ]);

        // Update quiz total points
        $totalPoints = Question::where('questionable_id', $quiz->id)
            ->where('questionable_type', Quiz::class)
            ->sum('points');

        $quiz->update(['total_points' => $totalPoints]);

        $this->command->info("Quiz '{$quiz->title}' created successfully with 3 tests and 5 questions!");
    }
}
