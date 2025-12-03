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
        // === AQUÍ ESTÁ LA CLAVE ===
        // Ejecuta el comando de enviar dosis CADA MINUTO
        $schedule->command('dosis:enviar')
                 ->everyMinute()
                 ->withoutOverlapping(); // Evita que se ejecute dos veces si se tarda mucho
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