<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ProcessQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:process-cron
                            {--once : Process only one job}
                            {--queue=notifications,default : The queue(s) to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process queue jobs (optimized for cron jobs on shared hosting)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $queue = $this->option('queue');
        $once = $this->option('once');

        $this->info('Processing queue jobs...');
        $this->info('Queue(s): ' . $queue);

        try {
            if ($once) {
                // Process one job only
                Artisan::call('queue:work', [
                    'connection' => 'database',
                    '--once' => true,
                    '--queue' => $queue,
                    '--tries' => 3,
                    '--timeout' => 90,
                ]);
            } else {
                // Process all jobs until empty
                Artisan::call('queue:work', [
                    'connection' => 'database',
                    '--stop-when-empty' => true,
                    '--queue' => $queue,
                    '--tries' => 3,
                    '--timeout' => 90,
                ]);
            }

            $output = Artisan::output();
            $this->line($output);
            
            $this->info('Queue processing completed successfully.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error processing queue: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
