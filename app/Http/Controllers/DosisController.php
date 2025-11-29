<?php

namespace App\Http\Controllers;

use App\Models\DosisProgramada;
use App\Models\DetalleTratamiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DosisController extends Controller
{
    // GET - Agenda semanal (Vista principal tipo Google Calendar)
    public function agendaSemanal(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio', 
        ]);

        $usuarioId = Auth::id();
        // Parseamos las fechas que vienen del request
        $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = Carbon::parse($request->fecha_fin)->endOfDay();

        // Obtener todas las dosis programadas para el rango de fechas
        $dosisSemana = DosisProgramada::whereHas('detalleTratamiento.tratamiento', function($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId);
            })
            ->whereBetween('fecha_hora', [$fechaInicio, $fechaFin])
            ->with([
                'detalleTratamiento.medicamento',
                'detalleTratamiento.tratamiento'
            ])
            ->orderBy('fecha_hora')
            ->get();

        // El formateo de dosis (map)
        $dosisFormateadas = $dosisSemana->map(function($dosis) {
            return [
                'id' => $dosis->id,
                'title' => $dosis->detalleTratamiento->medicamento->nombre,
                'start' => $dosis->fecha_hora->toIso8601String(),
                'end' => $dosis->fecha_hora->copy()->addMinutes(30)->toIso8601String(), // Duración de 30 min para visualización
                'color' => $this->getColorImportancia($dosis->detalleTratamiento->medicamento->importancia),
                'estado' => $dosis->estado,
                'tomada' => $dosis->tomada,
                'medicamento' => [
                    'nombre' => $dosis->detalleTratamiento->medicamento->nombre,
                    'importancia' => $dosis->detalleTratamiento->medicamento->importancia,
                    'cantidad_por_toma' => $dosis->detalleTratamiento->cantidad_por_toma,
                    'instrucciones' => $dosis->detalleTratamiento->instrucciones,
                ],
                'tratamiento' => [
                    'nombre' => $dosis->detalleTratamiento->tratamiento->nombre_tratamiento,
                    'fecha_inicio' => $dosis->detalleTratamiento->tratamiento->fecha_inicio,
                    'fecha_fin' => $dosis->detalleTratamiento->tratamiento->fecha_fin,
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'dosis' => $dosisFormateadas
            ]
        ]);
    }

    // GET - Agenda mensual
    public function agendaMensual(Request $request)
    {
        $request->validate([
            'mes' => 'sometimes|date_format:Y-m', // Formato: 2025-01
        ]);

        $usuarioId = Auth::id();
        
        $mes = $request->mes ? Carbon::parse($request->mes) : Carbon::now();
        $fechaInicio = $mes->copy()->startOfMonth();
        $fechaFin = $mes->copy()->endOfMonth();

        $dosisMes = DosisProgramada::whereHas('detalleTratamiento.tratamiento', function($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId);
            })
            ->whereBetween('fecha_hora', [$fechaInicio, $fechaFin])
            ->with([
                'detalleTratamiento.medicamento',
                'detalleTratamiento.tratamiento'
            ])
            ->orderBy('fecha_hora')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'mes' => $mes->format('Y-m'),
                'mes_nombre' => $mes->locale('es')->monthName,
                'dosis' => $dosisMes
            ]
        ]);
    }

    // GET - Dosis pendientes de hoy
    public function pendientesHoy()
    {
        $usuarioId = Auth::id();
        $hoyInicio = Carbon::now()->startOfDay();
        $hoyFin = Carbon::now()->endOfDay();

        $dosisHoy = DosisProgramada::whereHas('detalleTratamiento.tratamiento', function($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId)
                      ->where('estado', 'activo');
            })
            ->whereBetween('fecha_hora', [$hoyInicio, $hoyFin])
            ->where('estado', 'pendiente')
            ->with([
                'detalleTratamiento.medicamento',
                'detalleTratamiento.tratamiento'
            ])
            ->orderBy('fecha_hora')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'fecha' => Carbon::now()->toDateString(),
                'total_pendientes' => $dosisHoy->count(),
                'dosis' => $dosisHoy
            ]
        ]);
    }

    // PUT - Marcar dosis como tomada/omitida
    public function marcarDosis(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:tomada,omitida',
            'notas_toma' => 'nullable|string|max:500'
        ]);

        $dosis = DosisProgramada::whereHas('detalleTratamiento.tratamiento', function($query) {
                $query->where('usuario_id', Auth::id());
            })
            ->find($id);

        if (!$dosis) {
            return response()->json([
                'success' => false,
                'error' => 'Dosis no encontrada'
            ], 404);
        }

        $dosis->estado = $request->estado;
        $dosis->tomada = $request->estado === 'tomada';
        $dosis->fecha_hora_tomada = $request->estado === 'tomada' ? now() : null;
        $dosis->notas_toma = $request->notas_toma;
        $dosis->save();

        return response()->json([
            'success' => true,
            'message' => $request->estado === 'tomada' ? 'Dosis marcada como tomada' : 'Dosis marcada como omitida',
            'data' => $dosis->load('detalleTratamiento.medicamento')
        ]);
    }

    // GET - Estadísticas de adherencia
    public function estadisticasAdherencia(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'sometimes|date',
            'fecha_fin' => 'sometimes|date|after_or_equal:fecha_inicio',
        ]);

        $usuarioId = Auth::id();
        
        $fechaInicio = $request->fecha_inicio 
            ? Carbon::parse($request->fecha_inicio)
            : Carbon::now()->startOfMonth();
        $fechaFin = $request->fecha_fin 
            ? Carbon::parse($request->fecha_fin)
            : Carbon::now()->endOfMonth();

        $dosisPeriodo = DosisProgramada::whereHas('detalleTratamiento.tratamiento', function($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId);
            })
            ->whereBetween('fecha_hora', [$fechaInicio, $fechaFin])
            ->get();

        $total = $dosisPeriodo->count();
        $tomadas = $dosisPeriodo->where('estado', 'tomada')->count();
        $omitidas = $dosisPeriodo->where('estado', 'omitida')->count();
        $pendientes = $dosisPeriodo->where('estado', 'pendiente')->count();

        $porcentajeAdherencia = $total > 0 ? round(($tomadas / $total) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'periodo' => [
                    'inicio' => $fechaInicio->toDateString(),
                    'fin' => $fechaFin->toDateString(),
                ],
                'estadisticas' => [
                    'total_dosis' => $total,
                    'tomadas' => $tomadas,
                    'omitidas' => $omitidas,
                    'pendientes' => $pendientes,
                    'porcentaje_adherencia' => $porcentajeAdherencia
                ]
            ]
        ]);
    }

    // GET - Próximas dosis (para notificaciones)
    public function proximasDosis()
    {
        $usuarioId = Auth::id();
        $ahora = Carbon::now();
        $limite = $ahora->copy()->addHours(4); // Próximas 4 horas

        $proximasDosis = DosisProgramada::whereHas('detalleTratamiento.tratamiento', function($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId)
                      ->where('estado', 'activo');
            })
            ->whereBetween('fecha_hora', [$ahora, $limite])
            ->where('estado', 'pendiente')
            ->with([
                'detalleTratamiento.medicamento',
                'detalleTratamiento.tratamiento'
            ])
            ->orderBy('fecha_hora')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $proximasDosis
        ]);
    }

    /**
     * Obtener color según importancia del medicamento
     */
    private function getColorImportancia($importancia)
    {
        $colores = [
            'baja' => '#10B981',    // Verde
            'media' => '#F59E0B',   // Amarillo
            'alta' => '#EF4444',    // Rojo
            'critica' => '#7C3AED', // Violeta
        ];

        return $colores[$importancia] ?? '#6B7280'; // Gris por defecto
    }
}