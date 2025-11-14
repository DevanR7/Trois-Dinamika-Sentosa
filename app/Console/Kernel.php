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
        // Menjalankan penyusutan BUPAN LALU (--month tidak diset)
        // pada jam 1:00 pagi, di hari pertama setiap bulan.
        $schedule->command('accounting:run-depreciation')
                 ->monthlyOn(1, '01:00')
                 ->withoutOverlapping(); // Mencegah tumpang tindih
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
