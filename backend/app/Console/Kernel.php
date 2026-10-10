<?php

namespace App\Console;

use App\Jobs\CleanupOldNotifications;
use App\Jobs\GenerateContributionSchedules;
use App\Jobs\GenerateMonthlyReport;
use App\Jobs\SendPaymentReminders;
use App\Jobs\UpdateLateContributions;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new UpdateLateContributions)
            ->everySixHours()
            ->withoutOverlapping()
            ->name('update-late-contributions');

        $schedule->job(new SendPaymentReminders)
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->name('send-payment-reminders');

        $schedule->job(new GenerateContributionSchedules)
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->name('generate-contribution-schedules');

        $schedule->job(new CleanupOldNotifications)
            ->dailyAt('00:30')
            ->withoutOverlapping()
            ->name('cleanup-old-notifications');

        $schedule->job(new GenerateMonthlyReport)
            ->monthlyOn(1, '00:15')
            ->withoutOverlapping()
            ->name('generate-monthly-report');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
