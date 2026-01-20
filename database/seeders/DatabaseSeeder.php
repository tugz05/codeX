<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\AcademicYearSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\AdminSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            AdminSeeder::class,
            AcademicYearSeeder::class,
            SectionSeeder::class,
            QuizSeeder::class,
        ]);
        
        // Create a test student user if it doesn't exist
        if (!User::where('email', 'student@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test Student',
                'email' => 'student@example.com',
                'account_type' => 'student',
            ]);
        }
    }
}
