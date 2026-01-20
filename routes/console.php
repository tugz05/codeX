<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule due date reminders to run daily at 9 AM
Schedule::command('notifications:send-due-date-reminders')
    ->dailyAt('09:00')
    ->timezone('UTC');
