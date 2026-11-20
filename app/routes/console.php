<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// noredock demo: the scheduler service runs this every minute (see /noredock).
Schedule::call(fn () => Cache::forever('noredock_scheduler_last_run', now()->toDateTimeString()))
    ->name('noredock-heartbeat')
    ->everyMinute();
