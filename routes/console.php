<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Twice-daily backups: 4:30 AM and 4:30 PM
Schedule::command('backup:clean')->daily()->at('04:00');
Schedule::command('backup:run')->daily()->at('04:30');
Schedule::command('backup:run')->daily()->at('16:30'); // 4:30 PM

// Monitor backup health once daily
Schedule::command('backup:monitor')->daily()->at('05:00');
