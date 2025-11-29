<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DosisProgramada;
use App\Notifications\DosisNotification;
use Carbon\Carbon;

class EnviarAlertasDosis extends Command
{
    protected $signature = 'dosis:enviar';
    protected $description = 'Revisa y envía notificaciones push de dosis pendientes';

    public function handle()
    {
        // 1. Hora actual (Asegúrate de haber puesto 'America/Mexico_City' en config/app.php)
        $ahora = Carbon::now(); 
        
        $this->info("Buscando dosis pendientes para: " . $ahora->toDateTimeString());

        // 2. Buscar dosis que:
        // - Su hora es AHORA o ya pasó (hasta hace 15 min para no avisar cosas viejas)
        // - No se han notificado
        // - No se han tomado
        $dosisPendientes = DosisProgramada::where('notificacion_enviada', false)
            ->where('estado', 'pendiente')
            ->where('fecha_hora', '<=', $ahora)
            ->where('fecha_hora', '>', $ahora->copy()->subMinutes(15)) 
            ->with(['detalleTratamiento.tratamiento.usuario', 'detalleTratamiento.medicamento'])
            ->get();

        if ($dosisPendientes->isEmpty()) {
            $this->info("No hay dosis para notificar en este momento.");
            return;
        }

        foreach ($dosisPendientes as $dosis) {
            // Navegamos por las relaciones para llegar al usuario
            // Dosis -> Detalle -> Tratamiento -> Usuario
            $usuario = $dosis->detalleTratamiento->tratamiento->usuario;
            $medicamento = $dosis->detalleTratamiento->medicamento;

            if ($usuario) {
                try {
                    $horaDosis = Carbon::parse($dosis->fecha_hora)->format('H:i');
                    
                    // ¡ENVIAR LA NOTIFICACIÓN!
                    $usuario->notify(new DosisNotification($medicamento->nombre, $horaDosis));
                    
                    $this->info("Mensaje enviado a {$usuario->nombre} - Med: {$medicamento->nombre}");
                    
                    // Marcar como enviada para no repetir
                    $dosis->notificacion_enviada = true;
                    $dosis->save();

                } catch (\Exception $e) {
                    $this->error("Error al enviar: " . $e->getMessage());
                }
            }
        }
    }
}