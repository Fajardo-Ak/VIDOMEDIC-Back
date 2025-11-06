<?php

namespace App\Http\Controllers;

use App\Models\Tratamiento;
use App\Models\Medicamento;
use App\Models\DetalleTratamiento;
use App\Models\DosisProgramada;
use App\Services\GeneradorDosisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TratamientoController extends Controller
{
    protected $generadorDosis;

    public function __construct(GeneradorDosisService $generadorDosis)
    {
        $this->generadorDosis = $generadorDosis;
    }

    // GET - Listar tratamientos del usuario
    public function index()
    {
        $usuarioId = Auth::id();
        
        $tratamientos = Tratamiento::where('usuario_id', $usuarioId)
            ->with(['detalleTratamientos.medicamento', 'detalleTratamientos.dosisProgramadas'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tratamientos
        ]);
    }

    // POST - Crear nuevo tratamiento completo
    public function store(Request $request)
    {
        $request->validate([
            'nombre_tratamiento' => 'required|string|max:100',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'notas' => 'nullable|string',
            'medicamentos' => 'required|array|min:1',
            'medicamentos.*.medicamento_id' => 'required|exists:medicamentos,id',
            'medicamentos.*.tipo_frecuencia' => 'required|in:horas,dias,horarios_fijos,semanal',
            'medicamentos.*.cantidad_por_toma' => 'required|string|max:100',
            'medicamentos.*.instrucciones' => 'nullable|string|max:255',
            // Validaciones condicionales según tipo_frecuencia
            'medicamentos.*.valor_frecuencia' => 'required_if:medicamentos.*.tipo_frecuencia,horas,dias|integer|min:1',
            'medicamentos.*.horarios_fijos' => 'required_if:medicamentos.*.tipo_frecuencia,horarios_fijos,semanal,horas|array',
            'medicamentos.*.horarios_fijos.*' => 'date_format:H:i',
            'medicamentos.*.dias_semana' => 'required_if:medicamentos.*.tipo_frecuencia,semanal|array',
            'medicamentos.*.dias_semana.*' => 'in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
        ]);

        DB::beginTransaction();

        try {
            $usuarioId = Auth::id();

            // 1. CREAR EL TRATAMIENTO
            $tratamiento = Tratamiento::create([
                'usuario_id' => $usuarioId,
                'nombre_tratamiento' => $request->nombre_tratamiento,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'notas' => $request->notas,
                'estado' => 'activo'
            ]);

            // 2. PROCESAR CADA MEDICAMENTO
            foreach ($request->medicamentos as $medData) {
                // Crear detalle_tratamiento
                $detalle = DetalleTratamiento::create([
                    'tratamiento_id' => $tratamiento->id,
                    'medicamento_id' => $medData['medicamento_id'],
                    'tipo_frecuencia' => $medData['tipo_frecuencia'],
                    'valor_frecuencia' => $medData['valor_frecuencia'] ?? null,
                    'horarios_fijos' => isset($medData['horarios_fijos']) ? json_encode($medData['horarios_fijos']) : null,
                    'dias_semana' => isset($medData['dias_semana']) ? json_encode($medData['dias_semana']) : null,
                    'cantidad_por_toma' => $medData['cantidad_por_toma'],
                    'instrucciones' => $medData['instrucciones'] ?? null,
                ]);

                // 3. GENERAR DOSIS AUTOMÁTICAMENTE
                $this->generadorDosis->generarParaDetalle($detalle, $tratamiento);
            }

            DB::commit();

            // Cargar relaciones para la respuesta
            $tratamiento->load(['detalleTratamientos.medicamento', 'detalleTratamientos.dosisProgramadas']);

            return response()->json([
                'success' => true,
                'data' => $tratamiento,
                'message' => 'Tratamiento creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'error' => 'Error al crear el tratamiento: ' . $e->getMessage()
            ], 500);
        }
    }

    // GET - Mostrar un tratamiento específico
    public function show($id)
    {
        $usuarioId = Auth::id();
        
        $tratamiento = Tratamiento::where('usuario_id', $usuarioId)
            ->with([
                'detalleTratamientos.medicamento',
                'detalleTratamientos.dosisProgramadas'
            ])
            ->find($id);

        if (!$tratamiento) {
            return response()->json([
                'success' => false,
                'error' => 'Tratamiento no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tratamiento
        ]);
    }

    // PUT - Actualizar tratamiento
    public function update(Request $request, $id)
    {
        $usuarioId = Auth::id();
        $tratamiento = Tratamiento::where('usuario_id', $usuarioId)->find($id);

        if (!$tratamiento) {
            return response()->json([
                'success' => false,
                'error' => 'Tratamiento no encontrado'
            ], 404);
        }

        $request->validate([
            'nombre_tratamiento' => 'sometimes|required|string|max:100',
            'fecha_inicio' => 'sometimes|required|date',
            'fecha_fin' => 'sometimes|required|date|after_or_equal:fecha_inicio',
            'estado' => 'sometimes|in:activo,completado,cancelado',
            'notas' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // Actualizar tratamiento
            $tratamiento->update($request->only([
                'nombre_tratamiento', 'fecha_inicio', 'fecha_fin', 'estado', 'notas'
            ]));

            // Si cambian las fechas, regenerar dosis para todos los detalles
            if ($request->has('fecha_inicio') || $request->has('fecha_fin')) {
                foreach ($tratamiento->detalleTratamientos as $detalle) {
                    // Eliminar dosis existentes
                    $detalle->dosisProgramadas()->delete();
                    // Regenerar dosis con nuevas fechas
                    $this->generadorDosis->generarParaDetalle($detalle, $tratamiento);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $tratamiento->fresh(['detalleTratamientos.medicamento']),
                'message' => 'Tratamiento actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar el tratamiento: ' . $e->getMessage()
            ], 500);
        }
    }

    // DELETE - Eliminar tratamiento
    public function destroy($id)
    {
        $usuarioId = Auth::id();
        $tratamiento = Tratamiento::where('usuario_id', $usuarioId)->find($id);

        if (!$tratamiento) {
            return response()->json([
                'success' => false,
                'error' => 'Tratamiento no encontrado'
            ], 404);
        }

        $tratamiento->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tratamiento eliminado correctamente'
        ]);
    }

    // GET - Verificar si existe tratamiento activo
    public function verificarActivo()
    {
        $usuarioId = Auth::id();
        
        $tratamientoActivo = Tratamiento::where('usuario_id', $usuarioId)
            ->where('estado', 'activo')
            ->first();

        return response()->json([
            'success' => true,
            'tiene_activo' => !is_null($tratamientoActivo),
            'tratamiento' => $tratamientoActivo
        ]);
    }
}