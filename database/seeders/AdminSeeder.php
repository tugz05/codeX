<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin already exists
        $adminExists = User::where('email', 'admin@codex.com')->exists();

        if (!$adminExists) {
            User::create([
                'name' => 'Administrator',
                'email' => 'admin@codex.com',
                'password' => Hash::make('admin123'), // Change this password in production!
                'account_type' => 'admin',
            ]);

            $this->command->info('Admin account created successfully!');
            $this->command->info('Email: admin@codex.com');
            $this->command->info('Password: admin123');
            $this->command->warn('⚠️  Please change the default password after first login!');
        } else {
            $this->command->info('Admin account already exists.');
        }
    }
}
