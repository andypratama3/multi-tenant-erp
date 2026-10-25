<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

// Daily database backup at 02:00 WIB (UTC+8 = 18:00 UTC)
Schedule::command('backup:run')->dailyAt('18:00')->onFailure(function () {
    Log::error('Backup failed');
});

// Prune old backups weekly
Schedule::command('backup:clean')->weekly();

// Horizon snapshot every 5 minutes for metrics
Schedule::command('horizon:snapshot')->everyFiveMinutes();
