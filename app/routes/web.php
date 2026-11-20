<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// noredock demo: touches every service once.
// - db:    counts visits (database cache store in PostgreSQL)
// - queue: puts a job on the queue; the worker sends a mail
// - mail:  the mail shows up in Mailpit
// - vite:  the page loads its CSS/JS from the Vite dev server
Route::get('/noredock', function () {
    Cache::add('noredock_visits', 0);  // increment() needs an existing key in the database store
    $visit = Cache::increment('noredock_visits');

    dispatch(function () use ($visit) {
        Mail::raw(
            "Hello from the norevel queue worker.\n\nThis mail was queued by visit #{$visit} of /noredock.",
            fn ($message) => $message->to('dev@norevel.local')->subject("norevel: visit #{$visit}")
        );
    });

    $status = [
        'php' => PHP_VERSION,
        'laravel' => app()->version(),
        'database' => DB::connection()->getDriverName().' · '.explode(' on ', DB::scalar('select version()'))[0],
        'visits' => $visit,
        'jobs_waiting' => DB::table('jobs')->count(),
        'failed_jobs' => DB::table('failed_jobs')->count(),
        'scheduler_last_run' => Cache::get('noredock_scheduler_last_run', 'not yet (runs every minute)'),
        'mailer' => config('mail.default').' → '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'),
        'isolation' => env('NOREDOCK_ISOLATION', 'none'),
        'noredock_service' => env('NOREDOCK_NAME', '-'),
    ];

    return request()->wantsJson() ? $status : view('noredock', ['status' => $status]);
});
