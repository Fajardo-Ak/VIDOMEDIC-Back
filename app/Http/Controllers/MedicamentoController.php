<?php

namespace App\Http\Controllers;

use App\Models\Medicamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicamentoController extends Controller
{
    // GET - Listar medicamentos del usuario
    public function index()
    {
        $usuarioId = Auth::id();
        
        $medicamentos = Medicamento::where('usuario_id', $usuarioId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $medicamentos
        ]);
    }

    // POST - Crear nuevo medicamento en el catálogo
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'via_administracion' => 'required|in:Oral,Inyectable,Tópica,Oftálmica,Ótica,Nasal,Rectal,Vaginal,Inhalada,Otro',
            'via_administracion_personalizada' => 'nullable|string|max:100',
            'presentacion' => 'nullable|string|max:150',
            'importancia' => 'required|in:baja,media,alta,critica',
        ]);

        $usuarioId = Auth::id();

        // Verificar si ya existe un medicamento con el mismo nombre
        $medicamentoExistente = Medicamento::where('usuario_id', $usuarioId)
            ->where('nombre', $request->nombre)
            ->first();

        if ($medicamentoExistente) {
            return response()->json([
                'success' => false,
                'error' => 'Ya existe un medicamento con este nombre en tu catálogo'
            ], 409);
        }

        $medicamento = Medicamento::create([
            'usuario_id' => $usuarioId,
            'nombre' => $request->nombre,
            'via_administracion' => $request->via_administracion,
            'via_administracion_personalizada' => $request->via_administracion_personalizada,
            'presentacion' => $request->presentacion,
            'importancia' => $request->importancia,
            'activo' => true
        ]);

        return response()->json([
            'success' => true,
            'data' => $medicamento,
            'message' => 'Medicamento agregado al catálogo'
        ], 201);
    }

    // GET - Buscar medicamentos para autocomplete
    public function buscar(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2'
        ]);

        $usuarioId = Auth::id();
        
        $medicamentos = Medicamento::where('usuario_id', $usuarioId)
            ->where('activo', true)
            ->where('nombre', 'like', '%' . $request->q . '%')
            ->select('id', 'nombre', 'presentacion', 'importancia')
            ->orderBy('nombre')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $medicamentos
        ]);
    }

    // GET - Mostrar medicamento específico
    public function show($id)
    {
        $usuarioId = Auth::id();
        
        $medicamento = Medicamento::where('usuario_id', $usuarioId)
            ->with(['detalleTratamientos.tratamiento']) // Cargar tratamientos donde se usa
            ->find($id);

        if (!$medicamento) {
            return response()->json([
                'success' => false,
                'error' => 'Medicamento no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $medicamento
        ]);
    }

    // PUT - Actualizar medicamento
    public function update(Request $request, $id)
    {
        $usuarioId = Auth::id();
        
        $medicamento = Medicamento::where('usuario_id', $usuarioId)
            ->find($id);

        if (!$medicamento) {
            return response()->json([
                'success' => false,
                'error' => 'Medicamento no encontrado'
            ], 404);
        }

        $request->validate([
            'nombre' => 'sometimes|required|string|max:100',
            'via_administracion' => 'sometimes|required|in:Oral,Inyectable,Tópica,Oftálmica,Ótica,Nasal,Rectal,Vaginal,Inhalada,Otro',
            'via_administracion_personalizada' => 'nullable|string|max:100',
            'presentacion' => 'nullable|string|max:150',
            'importancia' => 'sometimes|required|in:baja,media,alta,critica',
            'activo' => 'sometimes|boolean'
        ]);

        $medicamento->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $medicamento,
            'message' => 'Medicamento actualizado correctamente'
        ]);
    }

    // DELETE - Desactivar medicamento (eliminación lógica)
    public function destroy($id)
    {
        $usuarioId = Auth::id();
        
        $medicamento = Medicamento::where('usuario_id', $usuarioId)
            ->find($id);

        if (!$medicamento) {
            return response()->json([
                'success' => false,
                'error' => 'Medicamento no encontrado'
            ], 404);
        }

        // Verificar si el medicamento está en uso en tratamientos activos
        $enUso = $medicamento->detalleTratamientos()
            ->whereHas('tratamiento', function($query) {
                $query->where('estado', 'activo');
            })
            ->exists();

        if ($enUso) {
            return response()->json([
                'success' => false,
                'error' => 'No se puede eliminar el medicamento porque está en uso en tratamientos activos'
            ], 409);
        }

        // Desactivar en lugar de eliminar
        $medicamento->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Medicamento eliminado del catálogo'
        ]);
    }

    // GET - Medicamentos más usados
    public function masUsados()
    {
        $usuarioId = Auth::id();
        
        $medicamentosMasUsados = Medicamento::where('usuario_id', $usuarioId)
            ->where('activo', true)
            ->withCount(['detalleTratamientos as veces_usado'])
            ->orderBy('veces_usado', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $medicamentosMasUsados
        ]);
    }
}