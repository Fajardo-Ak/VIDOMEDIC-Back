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

class TratamientoController extends Controller
{
    protected $generadorDosis;

    public function __construct(GeneradorDosisService $generadorDosis)
    {
        $this->generadorDosis = $generadorDosis;
    }

    // GET - Listar tratamientos del usuario
    // (Sin cambios)
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

    // --- INICIO DE MODIFICACIÓN 'store' ---
    // POST - Crear nuevo tratamiento completo
    public function store(Request $request)
    {
        $usuarioId = Auth::id(); // Obtenemos el ID de usuario al inicio

        // --- CAMBIO 1: Nueva Validación ---
        $request->validate([
            'nombre_tratamiento' => 'required|string|max:100',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'notas' => 'nullable|string',
            'medicamentos' => 'required|array|min:1',

            // --- Nuevas reglas de validación para el "Súper-Formulario" ---
            'medicamentos.*.medicamento_id' => 'nullable|exists:medicamentos,id', // Ahora puede ser nulo
            'medicamentos.*.medicamento_nombre' => 'required|string|max:100',
            'medicamentos.*.via_administracion' => 'required|in:Oral,Inyectable,Tópica,Oftálmica,Ótica,Nasal,Rectal,Vaginal,Inhalada,Otro',
            'medicamentos.*.presentacion' => 'nullable|string|max:150',
            'medicamentos.*.importancia' => 'required|in:baja,media,alta,critica',
            // --- Fin de nuevas reglas ---

            'medicamentos.*.tipo_frecuencia' => 'required|in:horas,dias,horarios_fijos,semanal',
            'medicamentos.*.cantidad_por_toma' => 'required|string|max:100',
            'medicamentos.*.valor_frecuencia' => 'required_if:medicamentos.*.tipo_frecuencia,horas,dias|integer|min:1',
            'medicamentos.*.horarios_fijos' => 'required_if:medicamentos.*.tipo_frecuencia,horarios_fijos,semanal,horas|array',
            'medicamentos.*.horarios_fijos.*' => 'date_format:H:i',
            'medicamentos.*.dias_semana' => 'required_if:medicamentos.*.tipo_frecuencia,semanal|array',
            'medicamentos.*.dias_semana.*' => 'in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
        ]);
        // --- FIN CAMBIO 1 ---

        DB::beginTransaction();

        try {
            $tratamiento = Tratamiento::create([
                'usuario_id' => $usuarioId,
                'nombre_tratamiento' => $request->nombre_tratamiento,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'notas' => $request->notas,
                'estado' => 'activo'
            ]);

            // --- CAMBIO 2: Lógica "Inteligente" de Creación ---
            foreach ($request->medicamentos as $medData) {
                
                $medicamentoIdParaUsar = $medData['medicamento_id'];

                // Si 'medicamento_id' es nulo, significa que es un medicamento nuevo (Caso B)
                if (is_null($medicamentoIdParaUsar)) {
                    // Usamos firstOrCreate para evitar duplicados en el catálogo
                    $nuevoMed = Medicamento::firstOrCreate(
                        [
                            'usuario_id' => $usuarioId, // Importante: buscar solo los del usuario
                            'nombre' => $medData['medicamento_nombre'],
                            'presentacion' => $medData['presentacion'] ?? null,
                        ],
                        [ // Datos que se usarán solo si se CREA
                            'via_administracion' => $medData['via_administracion'],
                            'importancia' => $medData['importancia'],
                            'activo' => true
                        ]
                    );
                    $medicamentoIdParaUsar = $nuevoMed->id;
                }
                // Si el ID no era nulo, $medicamentoIdParaUsar ya tiene el valor correcto (Caso A)

                // Ahora creamos el DetalleTratamiento con el ID correcto
                $detalle = DetalleTratamiento::create([
                    'tratamiento_id' => $tratamiento->id,
                    'medicamento_id' => $medicamentoIdParaUsar, // <-- ID Correcto
                    'tipo_frecuencia' => $medData['tipo_frecuencia'],
                    'valor_frecuencia' => $medData['valor_frecuencia'] ?? null,
                    'horarios_fijos' => isset($medData['horarios_fijos']) ? json_encode($medData['horarios_fijos']) : null,
                    'dias_semana' => isset($medData['dias_semana']) ? json_encode($medData['dias_semana']) : null,
                    'cantidad_por_toma' => $medData['cantidad_por_toma'],
                ]);

                $this->generadorDosis->generarParaDetalle($detalle, $tratamiento);
            }
            // --- FIN CAMBIO 2 ---

            DB::commit();

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
    // --- FIN MODIFICACIÓN 'store' ---


    // GET - Mostrar un tratamiento específico
    // (Sin cambios)
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

    // --- INICIO MODIFICACIÓN 'update' ---
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

        // --- CAMBIO 1: Validación actualizada (igual a 'store') ---
        $request->validate([
            'nombre_tratamiento' => 'sometimes|required|string|max:100',
            'fecha_inicio' => 'sometimes|required|date',
            'fecha_fin' => 'sometimes|required|date|after_or_equal:fecha_inicio',
            'notas' => 'nullable|string',
            'estado' => 'sometimes|in:activo,completado,cancelado',

            'medicamentos' => 'required|array|min:1',
            // --- Nuevas reglas ---
            'medicamentos.*.medicamento_id' => 'nullable|exists:medicamentos,id',
            'medicamentos.*.medicamento_nombre' => 'required|string|max:100',
            'medicamentos.*.via_administracion' => 'required|in:Oral,Inyectable,Tópica,Oftálmica,Ótica,Nasal,Rectal,Vaginal,Inhalada,Otro',
            'medicamentos.*.presentacion' => 'nullable|string|max:150',
            'medicamentos.*.importancia' => 'required|in:baja,media,alta,critica',
            // --- Fin nuevas reglas ---

            'medicamentos.*.tipo_frecuencia' => 'required|in:horas,dias,horarios_fijos,semanal',
            'medicamentos.*.cantidad_por_toma' => 'required|string|max:100',
            'medicamentos.*.valor_frecuencia' => 'required_if:medicamentos.*.tipo_frecuencia,horas,dias|integer|min:1',
            'medicamentos.*.horarios_fijos' => 'required_if:medicamentos.*.tipo_frecuencia,horarios_fijos,semanal,horas|array',
            'medicamentos.*.horarios_fijos.*' => 'date_format:H:i',
            'medicamentos.*.dias_semana' => 'required_if:medicamentos.*.tipo_frecuencia,semanal|array',
            'medicamentos.*.dias_semana.*' => 'in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
        ]);
        // --- FIN CAMBIO 1 ---

        DB::beginTransaction();
        try {
            // Actualizar datos generales
            $tratamiento->update($request->only([
                'nombre_tratamiento', 'fecha_inicio', 'fecha_fin', 'estado', 'notas'
            ]));

            // --- CAMBIO 2: Lógica "Destruir y Reconstruir" (con lógica inteligente) ---
            
            // 1. Borrar todos los detalles antiguos
            foreach ($tratamiento->detalleTratamientos as $detalle) {
                $detalle->dosisProgramadas()->delete();
                $detalle->delete();
            }

            // 2. Re-crear con la misma lógica "inteligente" de 'store'
            foreach ($request->medicamentos as $medData) {
                
                $medicamentoIdParaUsar = $medData['medicamento_id'];

                if (is_null($medicamentoIdParaUsar)) {
                    // Es un med nuevo, crearlo o encontrarlo
                    $nuevoMed = Medicamento::firstOrCreate(
                        [
                            'usuario_id' => $usuarioId,
                            'nombre' => $medData['medicamento_nombre'],
                            'presentacion' => $medData['presentacion'] ?? null,
                        ],
                        [ // Datos que solo se usan si se CREA
                            'via_administracion' => $medData['via_administracion'],
                            'importancia' => $medData['importancia'],
                            'activo' => true
                        ]
                    );
                    $medicamentoIdParaUsar = $nuevoMed->id;
                }

                // Crear el detalle con el ID correcto
                $detalle = DetalleTratamiento::create([
                    'tratamiento_id' => $tratamiento->id,
                    'medicamento_id' => $medicamentoIdParaUsar,
                    'tipo_frecuencia' => $medData['tipo_frecuencia'],
                    'valor_frecuencia' => $medData['valor_frecuencia'] ?? null,
                    'horarios_fijos' => isset($medData['horarios_fijos']) ? json_encode($medData['horarios_fijos']) : null,
                    'dias_semana' => isset($medData['dias_semana']) ? json_encode($medData['dias_semana']) : null,
                    'cantidad_por_toma' => $medData['cantidad_por_toma'],
                ]);

                // 3. Regenerar las dosis
                $this->generadorDosis->generarParaDetalle($detalle, $tratamiento);
            }
            // --- FIN CAMBIO 2 ---
            
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $tratamiento->fresh(['detalleTratamientos.medicamento', 'detalleTratamientos.dosisProgramadas']),
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
    // --- FIN MODIFICACIÓN 'update' ---

    // DELETE - Eliminar tratamiento COMPLETO
    // (Sin cambios)
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

    // Verificar tratamiento activo
    // (Mantenemos la corrección que ya tenías)
    public function verificarActivo()
    {
        $usuarioId = Auth::id();
        
        $tratamientoActivo = Tratamiento::where('usuario_id', $usuarioId)
            ->where('estado', 'activo')
            ->with(['detalleTratamientos.medicamento']) // ✅ Correcto
            ->first();

        return response()->json([
            'success' => true,
            'tiene_activo' => !is_null($tratamientoActivo),
            'tratamiento' => $tratamientoActivo
        ]);
    }
}