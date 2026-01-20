<?php
// database/seeders/AcademicYearSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('academic_years')->insert([
            [
                'semester' => '1st Semester',
                'start_year' => 2024,
                'end_year' => 2025,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'semester' => '2nd Semester',
                'start_year' => 2024,
                'end_year' => 2025,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'semester' => 'Midyear Term',
                'start_year' => 2025,
                'end_year' => 2025,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}
