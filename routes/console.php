<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily database backup (fast and focused).
Schedule::command('backup:run --only-db')->dailyAt('01:00');

// Weekly full backup (database + files).
Schedule::command('backup:run')->weeklyOn(0, '02:00');

// Keep backup storage healthy.
Schedule::command('backup:clean')->dailyAt('03:00');
Schedule::command('backup:monitor')->dailyAt('03:30');
