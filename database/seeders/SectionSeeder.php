<?php
// database/seeders/SectionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sections')->insert([
            [
                'user_id' => 1, // Make sure user with ID 1 exists
                'name' => 'BSCS 1-A',
                'schedule_from' => '08:00:00',
                'schedule_to' => '10:00:00',
                'day' => 'Monday-Wednesday',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'user_id' => 1,
                'name' => 'BSCS 2-B',
                'schedule_from' => '10:30:00',
                'schedule_to' => '12:30:00',
                'day' => 'Tuesday-Thursday',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}
