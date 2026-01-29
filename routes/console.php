<?php

use Illuminate\Support\Facades\Schedule;

// Twice-daily backups: 4:30 AM and 4:30 PM CET
Schedule::command('backup:clean')->daily()->at('04:00');
Schedule::command('backup:run')->daily()->at('04:30');
Schedule::command('backup:run')->daily()->at('16:30');

// Monitor backup health once daily
Schedule::command('backup:monitor')->daily()->at('05:00');

// Ping Forge heartbeat to monitor scheduler is running
if (app()->environment('production') && config('services.forge.heartbeat_url')) {
    Schedule::call(fn () => @file_get_contents(config('services.forge.heartbeat_url')))->everyFiveMinutes();
}
