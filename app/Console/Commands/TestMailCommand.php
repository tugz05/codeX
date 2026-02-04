<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TestMailCommand extends Command
{
    protected $signature = 'mail:test {email}';
    protected $description = 'Test mail configuration by sending a direct email';

    public function handle()
    {
        $email = $this->argument('email');

        $this->info('Testing mail configuration...');
        $this->info('Sending to: ' . $email);
        $this->newLine();

        try {
            // Test 1: Check mail config
            $this->info('Mail Configuration:');
            $this->line('  Mailer: ' . config('mail.default'));
            $this->line('  Host: ' . config('mail.mailers.smtp.host'));
            $this->line('  Port: ' . config('mail.mailers.smtp.port'));
            $this->line('  Encryption: ' . config('mail.mailers.smtp.encryption'));
            $this->line('  Username: ' . config('mail.mailers.smtp.username'));
            $this->line('  From: ' . config('mail.from.address'));
            $this->newLine();

            // Test 2: Send email directly (NOT queued)
            $this->info('Sending test email (direct, not queued)...');

            Mail::send('emails.notification', [
                'appName' => config('app.name', 'CodeX'),
                'title' => 'Test Email - Direct Send',
                'body' => 'This is a test email sent directly (not queued) to verify SMTP settings are working correctly. If you receive this, your mail configuration is correct!',
                'actionText' => 'Visit CodeX',
                'actionUrl' => url('/'),
                'preheader' => 'Test Email',
            ], function ($message) use ($email) {
                $message->to($email)
                    ->subject('CodeX - Test Email (Direct)');
            });

            $this->info('✓ Email sent successfully!');
            $this->line('Check inbox: ' . $email);
            $this->newLine();

            // Test 3: Send via notification system (queued)
            $this->info('Now testing queued notification...');

            $user = \App\Models\User::where('email', $email)->first();

            if (!$user) {
                $this->error('User not found with email: ' . $email);
                return Command::FAILURE;
            }

            $notification = new \App\Notifications\AssignmentCreatedNotification(
                'Test Assignment',
                'This is a test assignment notification sent via the queue system.',
                url('/')
            );

            $notification->onConnection('database')->onQueue('notifications');
            $user->notify($notification);

            $this->info('✓ Notification queued successfully!');
            $this->line('Run: php artisan queue:work database --once --queue=notifications');
            $this->line('Then check inbox again.');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('✗ Failed to send email!');
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());

            Log::error('Mail test failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
