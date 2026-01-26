<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Twice-daily backups: 4:30 AM and 4:30 PM CET
Schedule::command('backup:clean')->daily()->at('04:00');
Schedule::command('backup:run')->daily()->at('04:30');
Schedule::command('backup:run')->daily()->at('16:30');

// Monitor backup health once daily
Schedule::command('backup:monitor')->daily()->at('05:00');

// Ping Forge heartbeat to monitor scheduler is running
if (app()->environment('production')) {
    Schedule::call(fn() => @file_get_contents(env('FORGE_HEARTBEAT_URL')))->everyFiveMinutes();
}
