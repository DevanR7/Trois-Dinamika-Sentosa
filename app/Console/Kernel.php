<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;


class Kernel extends ConsoleKernel
{
    protected $commands = [
    \App\Console\Commands\CreateAdminUser::class,
    \App\Console\Commands\RunDepreciation::class,
];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('accounting:run-depreciation')
                 ->monthlyOn(1, '01:00')
                 ->withoutOverlapping(); 
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
