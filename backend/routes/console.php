<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Les échéances en retard sont calculées à la lecture, jamais stockées dans leur statut.
Schedule::command('payments:report-overdue')
    ->dailyAt('08:00')
    ->withoutOverlapping();
