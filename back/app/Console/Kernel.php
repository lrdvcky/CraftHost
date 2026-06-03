<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Каждые 5 минут: приостановка серверов с истёкшей арендой
        $schedule->command('servers:suspend-expired')->everyFiveMinutes();

        // Каждый день в 03:00: удаление серверов, висящих в suspended > 7 дней
        $schedule->command('servers:delete-abandoned')->dailyAt('03:00');

        // Каждые 5 минут: гарантируем online-mode=false на всех серверах
        $schedule->command('servers:enforce-cracked')->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
