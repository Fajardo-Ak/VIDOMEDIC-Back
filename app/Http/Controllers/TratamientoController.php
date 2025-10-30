<?php

namespace App\Http\Controllers;

use App\Models\Tratamiento;
use App\Models\Medicamento;
use App\Models\TratamientoMedicamento;
use App\Models\DosisProgramada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TratamientoController extends Controller
{
    // GET - Listar tratamientos del usuario
    public function index()
    {
        $usuarioId = Auth::id();
        $tratamientos = Tratamiento::where('usuario_id', $usuarioId)
            ->with(['tratamientoMedicamentos.medicamento']) // Cargar relaciones
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $tratamientos
        ]);
    }

    // POST - Crear nuevo tratamiento completo (con medicamentos y dosis programadas)
    public function store(Request $request)
    {
        // Validación principal del tratamiento
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'frecuencia' => 'required|array',
            'frecuencia.tipo' => 'required|in:horas,dias_semana',
            'frecuencia.valor' => 'required',
            'frecuencia.inicio' => 'required_if:frecuencia.tipo,horas',
            'importancia' => 'sometimes|in:Alta,Media,Baja',
            'notas' => 'nullable|string',
            'medicamentos' => 'required|array|min:1',
            'medicamentos.*.nombre' => 'required|string|max:100', // Puede ser nombre nuevo o ID existente
            'medicamentos.*.cantidad_por_toma' => 'required|string|max:50',
            'medicamentos.*.instrucciones' => 'nullable|string|max:255'
        ]);

        // Usamos transacción para asegurar que todo se guarde o nada
        DB::beginTransaction();

        try {
            $usuarioId = Auth::id();

            // 1. CREAR EL TRATAMIENTO
            $tratamiento = new Tratamiento();
            $tratamiento->usuario_id = $usuarioId;
            $tratamiento->fecha_inicio = $request->fecha_inicio;
            $tratamiento->fecha_fin = $request->fecha_fin;
            $tratamiento->frecuencia = json_encode($request->frecuencia);
            $tratamiento->importancia = $request->importancia ?? 'Media';
            $tratamiento->notas = $request->notas;
            $tratamiento->save();

            // 2. PROCESAR CADA MEDICAMENTO
            foreach ($request->medicamentos as $medData) {
                
                // LÓGICA PARA ENCONTRAR O CREAR MEDICAMENTO
                if (is_numeric($medData['nombre'])) {
                    // Si es número, buscar medicamento existente por ID
                    $medicamento = Medicamento::where('usuario_id', $usuarioId)
                        ->find($medData['nombre']);
                    
                    if (!$medicamento) {
                        throw new \Exception("Medicamento no encontrado");
                    }
                } else {
                    // Si es texto, buscar por nombre o crear nuevo
                    $medicamento = Medicamento::where('usuario_id', $usuarioId)
                        ->where('nombre', $medData['nombre'])
                        ->first();
                    
                    if (!$medicamento) {
                        // Crear nuevo medicamento en el catálogo
                        $medicamento = new Medicamento();
                        $medicamento->usuario_id = $usuarioId;
                        $medicamento->nombre = $medData['nombre'];
                        $medicamento->via_administracion = 'Oral'; // Valor por defecto
                        $medicamento->presentacion = ''; // Se puede dejar vacío inicialmente
                        $medicamento->save();
                    }
                }

                // 3. CREAR LA RELACIÓN TRATAMIENTO_MEDICAMENTO
                $tratamientoMedicamento = new TratamientoMedicamento();
                $tratamientoMedicamento->tratamiento_id = $tratamiento->id;
                $tratamientoMedicamento->medicamento_id = $medicamento->id;
                $tratamientoMedicamento->cantidad_por_toma = $medData['cantidad_por_toma'];
                $tratamientoMedicamento->instrucciones = $medData['instrucciones'] ?? null;
                $tratamientoMedicamento->save();

                // 4. CALCULAR Y CREAR LAS DOSIS PROGRAMADAS
                $this->crearDosisProgramadas($tratamiento, $tratamientoMedicamento);
            }

            // Si todo sale bien, confirmar la transacción
            DB::commit();

            // Cargar relaciones para la respuesta
            $tratamiento->load('tratamientoMedicamentos.medicamento');

            return response()->json([
                'success' => true,
                'data' => $tratamiento,
                'message' => 'Tratamiento creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            // Si algo falla, revertir todo
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
                'tratamientoMedicamentos.medicamento',
                'tratamientoMedicamentos.dosisProgramadas'
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
            'fecha_inicio' => 'sometimes|required|date',
            'fecha_fin' => 'sometimes|required|date|after_or_equal:fecha_inicio',
            'frecuencia' => 'sometimes|required|array',
            'importancia' => 'sometimes|in:Alta,Media,Baja',
            'notas' => 'nullable|string'
        ]);

        // Actualizar solo los campos que vienen en la request
        if ($request->has('fecha_inicio')) {
            $tratamiento->fecha_inicio = $request->fecha_inicio;
        }
        if ($request->has('fecha_fin')) {
            $tratamiento->fecha_fin = $request->fecha_fin;
        }
        if ($request->has('frecuencia')) {
            $tratamiento->frecuencia = json_encode($request->frecuencia);
        }
        if ($request->has('importancia')) {
            $tratamiento->importancia = $request->importancia;
        }
        if ($request->has('notas')) {
            $tratamiento->notas = $request->notas;
        }

        $tratamiento->save();

        return response()->json([
            'success' => true,
            'data' => $tratamiento,
            'message' => 'Tratamiento actualizado correctamente'
        ]);
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

    // GET - Agenda semanal (Vista principal)
    public function agendaSemanal(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'sometimes|date', // Fecha de inicio de la semana
        ]);

        $usuarioId = Auth::id();
        
        // Si no viene fecha, usar la semana actual
        $fechaInicio = $request->fecha_inicio 
            ? Carbon::parse($request->fecha_inicio)
            : Carbon::now()->startOfWeek();
            
        $fechaFin = $fechaInicio->copy()->endOfWeek();

        // Obtener todas las dosis programadas para esta semana
        $dosisSemana = DosisProgramada::whereHas('tratamientoMedicamento.tratamiento', function($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId);
            })
            ->whereBetween('fecha_hora', [$fechaInicio, $fechaFin])
            ->with([
                'tratamientoMedicamento.medicamento',
                'tratamientoMedicamento.tratamiento'
            ])
            ->orderBy('fecha_hora')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'semana_inicio' => $fechaInicio->toDateString(),
                'semana_fin' => $fechaFin->toDateString(),
                'dosis' => $dosisSemana
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

        $dosisMes = DosisProgramada::whereHas('tratamientoMedicamento.tratamiento', function($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId);
            })
            ->whereBetween('fecha_hora', [$fechaInicio, $fechaFin])
            ->with([
                'tratamientoMedicamento.medicamento',
                'tratamientoMedicamento.tratamiento'
            ])
            ->orderBy('fecha_hora')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'mes' => $mes->format('Y-m'),
                'dosis' => $dosisMes
            ]
        ]);
    }

    // BUSCAR MEDICAMENTOS PARA AUTOCOMPLETE
    public function buscarMedicamentos(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2'
        ]);

        $usuarioId = Auth::id();
        $medicamentos = Medicamento::where('usuario_id', $usuarioId)
            ->where('nombre', 'like', '%' . $request->q . '%')
            ->select('id', 'nombre', 'presentacion')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $medicamentos
        ]);
    }

    // MARCAR DOSIS COMO TOMADA/PENDIENTE
    public function marcarDosis(Request $request, $id)
    {
        $request->validate([
            'tomada' => 'required|boolean'
        ]);

        $dosis = DosisProgramada::whereHas('tratamientoMedicamento.tratamiento', function($query) {
                $query->where('usuario_id', Auth::id());
            })
            ->find($id);

        if (!$dosis) {
            return response()->json([
                'success' => false,
                'error' => 'Dosis no encontrada'
            ], 404);
        }

        $dosis->tomada = $request->tomada;
        $dosis->save();

        return response()->json([
            'success' => true,
            'message' => $request->tomada ? 'Dosis marcada como tomada' : 'Dosis marcada como pendiente',
            'data' => $dosis
        ]);
    }

    /**
     * Función para calcular y crear todas las dosis programadas
     * basado en la frecuencia del tratamiento
     */
    private function crearDosisProgramadas($tratamiento, $tratamientoMedicamento)
    {
        $frecuencia = json_decode($tratamiento->frecuencia, true);
        $fechaInicio = Carbon::parse($tratamiento->fecha_inicio);
        $fechaFin = Carbon::parse($tratamiento->fecha_fin);
        
        $dosisProgramadas = [];

        if ($frecuencia['tipo'] === 'horas') {
            // Lógica para frecuencia por horas
            $horasIntervalo = $frecuencia['valor'];
            $horaInicio = $frecuencia['inicio']; // formato "08:00"
            
            $fechaActual = $fechaInicio->copy();
            
            // Ajustar la primera fecha a la hora de inicio
            list($hora, $minuto) = explode(':', $horaInicio);
            $fechaActual->setTime($hora, $minuto);

            // Si la hora de inicio ya pasó para hoy, empezar desde el siguiente intervalo
            if ($fechaActual->lt(Carbon::now())) {
                $fechaActual->addHours($horasIntervalo);
            }
            
            while ($fechaActual <= $fechaFin) {
                // Crear dosis programada
                $dosisProgramadas[] = [
                    'tratamiento_medicamento_id' => $tratamientoMedicamento->id,
                    'fecha_hora' => $fechaActual->toDateTimeString(),
                    'tomada' => false,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                // Avanzar el intervalo de horas
                $fechaActual->addHours($horasIntervalo);
            }
        }
        // Aquí puedes agregar más tipos de frecuencia después (días de semana, etc.)

        // Insertar todas las dosis programadas de una vez
        if (!empty($dosisProgramadas)) {
            DosisProgramada::insert($dosisProgramadas);
        }
    }
}