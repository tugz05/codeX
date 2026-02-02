<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class TestQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:test 
                            {email : The email address to send test notification to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test queue system by sending a test email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info('Testing queue system...');
        $this->newLine();

        // Check queue configuration
        $this->info('✓ Checking configuration...');
        $queueConnection = config('queue.default');
        $this->line("  Queue connection: {$queueConnection}");

        if ($queueConnection !== 'database') {
            $this->warn("  Warning: Queue connection is not 'database'. Current: {$queueConnection}");
        }

        // Check database tables
        $this->info('✓ Checking database tables...');
        try {
            $jobsCount = DB::table('jobs')->count();
            $this->line("  Jobs table exists. Current jobs: {$jobsCount}");
            
            $failedJobsCount = DB::table('failed_jobs')->count();
            $this->line("  Failed jobs table exists. Failed jobs: {$failedJobsCount}");
        } catch (\Exception $e) {
            $this->error("  Error: Database tables not found. Run 'php artisan migrate'");
            return Command::FAILURE;
        }

        // Send test email
        $this->info('✓ Sending test email...');
        try {
            Mail::raw(
                "This is a test email from CodeX Queue System.\n\n" .
                "If you received this email, your queue system is working correctly!\n\n" .
                "Sent at: " . now()->toDateTimeString(),
                function ($message) use ($email) {
                    $message->to($email)
                        ->subject('CodeX Queue Test - ' . now()->format('Y-m-d H:i:s'));
                }
            );
            
            $this->line("  Test email queued to: {$email}");
        } catch (\Exception $e) {
            $this->error("  Error queuing email: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Check if job was added to queue
        $this->newLine();
        $this->info('✓ Checking queue...');
        $newJobsCount = DB::table('jobs')->count();
        $this->line("  Jobs in queue: {$newJobsCount}");

        if ($newJobsCount > $jobsCount) {
            $this->info("  ✓ Job successfully added to queue!");
        } else {
            $this->warn("  Warning: Job might not have been queued (check MAIL_MAILER setting)");
        }

        // Instructions
        $this->newLine();
        $this->info('Next steps:');
        $this->line('1. Run queue worker manually to process the job:');
        $this->line('   php artisan queue:work database --once');
        $this->newLine();
        $this->line('2. Or wait for cron job to process it (if configured)');
        $this->newLine();
        $this->line('3. Check if email was received at: ' . $email);
        $this->newLine();
        $this->line('4. Monitor queue status:');
        $this->line('   php artisan queue:monitor database');

        return Command::SUCCESS;
    }
}
