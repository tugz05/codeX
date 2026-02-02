<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class DiagnoseQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:diagnose';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose queue system configuration and status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('==============================================');
        $this->info('   QUEUE SYSTEM DIAGNOSTICS');
        $this->info('==============================================');
        $this->newLine();

        // 1. Check Environment Configuration
        $this->info('1. ENVIRONMENT CONFIGURATION');
        $this->info('----------------------------------------------');
        $queueConnection = config('queue.default');
        $this->line("  Queue Connection: {$queueConnection}");
        
        if ($queueConnection !== 'database') {
            $this->error("  ✗ Queue connection should be 'database' but is '{$queueConnection}'");
            $this->warn("  Fix: Set QUEUE_CONNECTION=database in .env file");
        } else {
            $this->info("  ✓ Queue connection is correctly set to 'database'");
        }

        $mailMailer = config('mail.default');
        $mailHost = config('mail.mailers.smtp.host');
        $this->line("  Mail Mailer: {$mailMailer}");
        $this->line("  Mail Host: {$mailHost}");
        $this->newLine();

        // 2. Check Database Tables
        $this->info('2. DATABASE TABLES');
        $this->info('----------------------------------------------');
        try {
            $jobsCount = DB::table('jobs')->count();
            $this->info("  ✓ Jobs table exists");
            $this->line("    Current jobs in queue: {$jobsCount}");
            
            if ($jobsCount > 0) {
                $this->warn("    WARNING: {$jobsCount} jobs are pending!");
                $oldestJob = DB::table('jobs')->orderBy('created_at', 'asc')->first();
                if ($oldestJob) {
                    $created = date('Y-m-d H:i:s', $oldestJob->created_at);
                    $this->line("    Oldest job created: {$created}");
                }
            }
            
            $failedJobsCount = DB::table('failed_jobs')->count();
            $this->info("  ✓ Failed jobs table exists");
            $this->line("    Failed jobs: {$failedJobsCount}");
            
            if ($failedJobsCount > 0) {
                $this->error("    ERROR: {$failedJobsCount} jobs have failed!");
                $this->line("    Run: php artisan queue:failed");
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Database tables not found!");
            $this->error("    Error: " . $e->getMessage());
            $this->warn("    Fix: Run 'php artisan migrate'");
            return Command::FAILURE;
        }
        $this->newLine();

        // 3. Check Queue Worker Status
        $this->info('3. QUEUE WORKER STATUS');
        $this->info('----------------------------------------------');
        $this->line("  To check if cron is running:");
        $this->line("  - Check Hostinger cron job execution log");
        $this->line("  - Look for 'Last Execution' timestamp");
        $this->newLine();

        // 4. Check Recent Jobs
        $this->info('4. RECENT JOBS ANALYSIS');
        $this->info('----------------------------------------------');
        $recentJobs = DB::table('jobs')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'queue', 'created_at', 'attempts']);
        
        if ($recentJobs->count() > 0) {
            $this->warn("  {$recentJobs->count()} recent jobs in queue:");
            foreach ($recentJobs as $job) {
                $created = date('Y-m-d H:i:s', $job->created_at);
                $this->line("    - Job ID: {$job->id}, Queue: {$job->queue}, Created: {$created}, Attempts: {$job->attempts}");
            }
        } else {
            $this->info("  ✓ Queue is empty (no pending jobs)");
        }
        $this->newLine();

        // 5. Check Failed Jobs
        $this->info('5. FAILED JOBS ANALYSIS');
        $this->info('----------------------------------------------');
        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(3)
            ->get(['id', 'queue', 'exception', 'failed_at']);
        
        if ($failedJobs->count() > 0) {
            $this->error("  {$failedJobs->count()} recent failed jobs:");
            foreach ($failedJobs as $job) {
                $this->line("    - Job ID: {$job->id}, Queue: {$job->queue}");
                $this->line("      Failed: {$job->failed_at}");
                $exceptionPreview = substr($job->exception, 0, 200);
                $this->line("      Error: {$exceptionPreview}...");
            }
            $this->newLine();
            $this->warn("  To retry failed jobs: php artisan queue:retry all");
        } else {
            $this->info("  ✓ No failed jobs");
        }
        $this->newLine();

        // 6. Test Email Configuration
        $this->info('6. EMAIL CONFIGURATION TEST');
        $this->info('----------------------------------------------');
        try {
            $testUser = User::where('account_type', 'student')->first();
            if ($testUser) {
                $this->line("  Test user found: {$testUser->email}");
                $this->info("  ✓ Can send emails to users");
            } else {
                $this->warn("  No student users found for testing");
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Error accessing users: " . $e->getMessage());
        }
        $this->newLine();

        // 7. Recommendations
        $this->info('7. RECOMMENDATIONS');
        $this->info('----------------------------------------------');
        
        if ($jobsCount > 0) {
            $this->warn("  ACTION REQUIRED: Process pending jobs");
            $this->line("  Run: php artisan queue:work database --once");
            $this->newLine();
        }
        
        if ($failedJobsCount > 0) {
            $this->warn("  ACTION REQUIRED: Review and retry failed jobs");
            $this->line("  Run: php artisan queue:failed");
            $this->line("  Run: php artisan queue:retry all");
            $this->newLine();
        }
        
        if ($queueConnection === 'database' && $jobsCount == 0 && $failedJobsCount == 0) {
            $this->info("  ✓ Queue system looks healthy!");
            $this->line("  Next: Ensure cron job is set up in Hostinger");
            $this->line("  Command: cd /home/YOUR_USERNAME/domains/YOUR_DOMAIN/public_html && /usr/bin/php artisan queue:work database --once --queue=notifications,default --tries=3 --timeout=90 > /dev/null 2>&1");
            $this->line("  Schedule: Every minute (* * * * *)");
        }

        $this->newLine();
        $this->info('==============================================');
        $this->info('   DIAGNOSTICS COMPLETE');
        $this->info('==============================================');

        return Command::SUCCESS;
    }
}
