<?php

namespace App\Services;

use App\Models\DetalleTratamiento;
use App\Models\Tratamiento;
use App\Models\DosisProgramada;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GeneradorDosisService
{
    /**
     * Genera todas las dosis programadas para un detalle de tratamiento
     */
    public function generarParaDetalle(DetalleTratamiento $detalle, Tratamiento $tratamiento)
    {
        $dosis = [];
        
        // $fechaInicio es "2025-11-06 00:00:00"
        $fechaInicio = Carbon::parse($tratamiento->fecha_inicio)->startOfDay(); 
        // $fechaFin es "2025-11-09 00:00:00"
        $fechaFin = Carbon::parse($tratamiento->fecha_fin)->startOfDay();

        // El final *real* es el inicio del día SIGUIENTE a la fecha de fin.
        // Ej: Si $fechaFin es "2025-11-09", $fechaFinReal será "2025-11-10 00:00:00"
        $fechaFinReal = $fechaFin->copy()->addDay()->startOfDay();

        switch ($detalle->tipo_frecuencia) {
            case 'horas':
                $dosis = $this->generarPorHoras($detalle, $fechaInicio, $fechaFinReal);
                break;
                
            case 'dias':
                $dosis = $this->generarPorDias($detalle, $fechaInicio, $fechaFinReal);
                break;
                
            case 'horarios_fijos':
                $dosis = $this->generarPorHorariosFijos($detalle, $fechaInicio, $fechaFinReal);
                break;
                
            case 'semanal':
                $dosis = $this->generarPorSemanal($detalle, $fechaInicio, $fechaFinReal);
                break;
                
            default:
                throw new \Exception("Tipo de frecuencia no válido: " . $detalle->tipo_frecuencia);
        }

        if (!empty($dosis)) {
            DosisProgramada::insert($dosis);
        }

        Log::info("Generadas " . count($dosis) . " dosis para medicamento ID: " . $detalle->medicamento_id);
    }

    /**
     * Genera dosis por frecuencia en horas (ej: cada 6 horas)
     * (Función correcta)
     */
    private function generarPorHoras(DetalleTratamiento $detalle, Carbon $fechaInicio, Carbon $fechaFinReal): array
    {
        $dosis = [];
        $intervaloHoras = $detalle->valor_frecuencia;
        
        $horarios = json_decode($detalle->horarios_fijos);
        $horaDeInicioStr = $horarios[0] ?? '08:00'; 
        
        list($hora, $minuto) = explode(':', $horaDeInicioStr);
        $fechaActual = $fechaInicio->copy()->setTime($hora, $minuto); // Ej: 2025-11-06 07:00:00

        // Bucle: < 2025-11-10 00:00:00
        while ($fechaActual < $fechaFinReal) { 
            
            // Asegurarnos de que la primera dosis no sea ANTES del inicio real
            if ($fechaActual < $fechaInicio) {
                 $fechaActual->addHours($intervaloHoras);
                 continue;
            }

            $dosis[] = [
                'tratamiento_medicamento_id' => $detalle->id,
                'fecha_hora' => $fechaActual->copy(),
                'estado' => 'pendiente',
                'tomada' => false,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $fechaActual->addHours($intervaloHoras);
        }

        return $dosis;
    }


    /**
     * Genera dosis por frecuencia en días (ej: cada 2 días)
     * (Función CORREGIDA)
     */
    private function generarPorDias(DetalleTratamiento $detalle, Carbon $fechaInicio, Carbon $fechaFinReal): array
    {
        $dosis = [];
        $intervaloDias = $detalle->valor_frecuencia;
        $horarios = json_decode($detalle->horarios_fijos) ?? ['08:00', '20:00']; 
        
        $fechaActual = $fechaInicio->copy();

        while ($fechaActual < $fechaFinReal) {
            foreach ($horarios as $hora) {
                $fechaHora = $fechaActual->copy()->setTime(
                    ...explode(':', $hora)
                );
                
                // Solo añadir si es DESPUÉS del inicio
                if ($fechaHora >= $fechaInicio) {
                    $dosis[] = [
                        'tratamiento_medicamento_id' => $detalle->id,
                        'fecha_hora' => $fechaHora,
                        'estado' => 'pendiente',
                        'tomada' => false,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }
            
            $fechaActual->addDays($intervaloDias);
        }

        return $dosis;
    }

    /**
     * Genera dosis por horarios fijos (ej: 08:00, 14:00, 20:00 todos los días)
     * (Función CORREGIDA)
     */
    private function generarPorHorariosFijos(DetalleTratamiento $detalle, Carbon $fechaInicio, Carbon $fechaFinReal): array
    {
        $dosis = [];
        $horarios = json_decode($detalle->horarios_fijos);
        
        $fechaActual = $fechaInicio->copy();

        while ($fechaActual < $fechaFinReal) {
            foreach ($horarios as $hora) {
                $fechaHora = $fechaActual->copy()->setTime(
                    ...explode(':', $hora)
                );
                
                // Solo añadir si es DESPUÉS del inicio
                if ($fechaHora >= $fechaInicio) {
                    $dosis[] = [
                        'tratamiento_medicamento_id' => $detalle->id,
                        'fecha_hora' => $fechaHora,
                        'estado' => 'pendiente',
                        'tomada' => false,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }
            
            $fechaActual->addDay();
        }

        return $dosis;
    }

    /**
     * Genera dosis por días específicos de la semana (ej: lunes, miércoles, viernes)
     * (Función CORREGIDA)
     */
    private function generarPorSemanal(DetalleTratamiento $detalle, Carbon $fechaInicio, Carbon $fechaFinReal): array
    {
        $dosis = [];
        $diasSemana = json_decode($detalle->dias_semana);
        $horarios = json_decode($detalle->horarios_fijos);
        
        $fechaActual = $fechaInicio->copy();

        $diasMap = [
            'lunes' => Carbon::MONDAY,
            'martes' => Carbon::TUESDAY,
            'miercoles' => Carbon::WEDNESDAY,
            'jueves' => Carbon::THURSDAY,
            'viernes' => Carbon::FRIDAY,
            'sabado' => Carbon::SATURDAY,
            'domingo' => Carbon::SUNDAY,
        ];

        while ($fechaActual < $fechaFinReal) {
            $nombreDia = strtolower($fechaActual->locale('es')->dayName);
            
            if (in_array($nombreDia, $diasSemana)) {
                foreach ($horarios as $hora) {
                    $fechaHora = $fechaActual->copy()->setTime(
                        ...explode(':', $hora)
                    );
                    
                    // Solo añadir si es DESPUÉS del inicio
                    if ($fechaHora >= $fechaInicio) {
                        $dosis[] = [
                            'tratamiento_medicamento_id' => $detalle->id,
                            'fecha_hora' => $fechaHora,
                            'estado' => 'pendiente',
                            'tomada' => false,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
            }
            
            $fechaActual->addDay();
        }

        return $dosis;
    }

    /**
     * Regenera dosis para un tratamiento completo (útil cuando se actualiza)
     */
    public function regenerarParaTratamiento(Tratamiento $tratamiento)
    {
        foreach ($tratamiento->detalleTratamientos as $detalle) {
            $detalle->dosisProgramadas()->delete();
            $this->generarParaDetalle($detalle, $tratamiento);
        }
    }
}