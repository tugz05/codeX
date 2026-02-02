<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\NotificationService;

class TestNotificationQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:test-notification {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test notification system by sending a test notification via queue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info('Testing Notification Queue System...');
        $this->newLine();

        // Find or create test user
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found!");
            $this->warn("Please provide an existing user's email address.");
            return Command::FAILURE;
        }

        $this->info("Found user: {$user->name} ({$user->email})");
        $this->newLine();

        // Check jobs before
        $jobsBefore = DB::table('jobs')->count();
        $this->line("Jobs in queue before: {$jobsBefore}");

        // Send test notification
        $this->info("Sending test notification...");
        try {
            $notificationService = app(NotificationService::class);
            
            $notificationService->sendEmailNotification(
                'assignment_created',
                $user,
                'Test Queue Notification',
                'This is a test notification to verify the queue system is working. If you receive this email, your queue system is properly configured!',
                url('/')
            );
            
            $this->info("✓ Notification dispatched successfully!");
        } catch (\Exception $e) {
            $this->error("✗ Error sending notification: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Check jobs after
        sleep(1); // Give it a moment
        $jobsAfter = DB::table('jobs')->count();
        $this->line("Jobs in queue after: {$jobsAfter}");
        $this->newLine();

        // Analyze result
        if ($jobsAfter > $jobsBefore) {
            $this->info("✓ SUCCESS! Job was added to queue!");
            $this->line("  Jobs added: " . ($jobsAfter - $jobsBefore));
            $this->newLine();
            
            $this->info("Next steps:");
            $this->line("1. Process the job manually:");
            $this->line("   php artisan queue:work database --once");
            $this->newLine();
            $this->line("2. Or wait for cron job to process it (1-5 minutes)");
            $this->newLine();
            $this->line("3. Check your email: {$email}");
            $this->newLine();
            
            // Show the job details
            $latestJob = DB::table('jobs')->latest('id')->first();
            if ($latestJob) {
                $this->line("Latest job details:");
                $this->line("  - ID: {$latestJob->id}");
                $this->line("  - Queue: {$latestJob->queue}");
                $this->line("  - Attempts: {$latestJob->attempts}");
                $created = date('Y-m-d H:i:s', $latestJob->created_at);
                $this->line("  - Created: {$created}");
            }
        } else {
            $this->error("✗ PROBLEM! Job was NOT added to queue!");
            $this->newLine();
            $this->warn("Possible issues:");
            $this->line("1. Queue connection might not be 'database'");
            $this->line("   Check: config('queue.default')");
            $this->line("2. Notification might not implement ShouldQueue");
            $this->line("3. Mail mailer might be 'log' instead of 'smtp'");
            $this->newLine();
            $this->line("Run diagnostics:");
            $this->line("  php artisan queue:diagnose");
        }

        return Command::SUCCESS;
    }
}
