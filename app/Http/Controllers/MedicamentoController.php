<?php

namespace App\Http\Controllers;

use App\Models\Medicamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicamentoController extends Controller
{
    // GET - Listado de medicamentos del usuario
    public function index()
    {
        $usuarioId = Auth::id();
        $medicamentos = Medicamento::where('usuario_id', $usuarioId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $medicamentos
        ]);
    }

    // POST - Crear nuevo medicamento
    public function store(Request $req)
    {
        $req->validate([
            'nombre' => 'required|string|max:100',
            'via_administracion' => 'required|in:Oral,Inyectable,Tópica,Otro',
            'via_administracion_personalizada' => 'nullable|string|max:50',
            'dosis' => 'required|string|max:100',
            'importancia' => 'sometimes|in:Alta,Media,Baja'
        ]);

        // Validar "Otro"
        if ($req->via_administracion === 'Otro' && empty($req->via_administracion_personalizada)) {
            return response()->json([
                'success' => false,
                'error' => 'Especifica la vía de administración cuando selecciona "Otro"'
            ], 422);
        }

        $medicamento = new Medicamento;
        $medicamento->usuario_id = Auth::id();
        $medicamento->nombre = $req->nombre;
        $medicamento->via_administracion = $req->via_administracion;
        $medicamento->via_administracion_personalizada = $req->via_administracion_personalizada;
        $medicamento->dosis = $req->dosis;
        $medicamento->importancia = $req->importancia ?? 'Baja';
        $medicamento->save();

        return response()->json([
            'success' => true,
            'data' => $medicamento
        ], 201);
    }

    // GET - Mostrar un medicamento
    public function show($id)
    {
        $usuarioId = Auth::id();
        $medicamento = Medicamento::where('usuario_id', $usuarioId)->find($id);

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
    public function update(Request $req, $id)
    {
        $usuarioId = Auth::id();
        $medicamento = Medicamento::where('usuario_id', $usuarioId)->find($id);

        if (!$medicamento) {
            return response()->json([
                'success' => false,
                'error' => 'Medicamento no encontrado'
            ], 404);
        }

        $req->validate([
            'nombre' => 'sometimes|required|string|max:100',
            'via_administracion' => 'sometimes|required|in:Oral,Inyectable,Tópica,Otro',
            'via_administracion_personalizada' => 'nullable|string|max:50',
            'dosis' => 'sometimes|required|string|max:100',
            'importancia' => 'sometimes|in:Alta,Media,Baja'
        ]);

        // Validar "Otro"
        if ($req->has('via_administracion') && 
            $req->via_administracion === 'Otro' && 
            empty($req->via_administracion_personalizada)) {
            return response()->json([
                'success' => false,
                'error' => 'Especifica la vía de administración cuando selecciona "Otro"'
            ], 422);
        }

        $medicamento->fill($req->all());
        $medicamento->save();

        return response()->json([
            'success' => true,
            'data' => $medicamento
        ]);
    }

    // DELETE - Eliminar medicamento
    public function destroy($id)
    {
        $usuarioId = Auth::id();
        $medicamento = Medicamento::where('usuario_id', $usuarioId)->find($id);

        if (!$medicamento) {
            return response()->json([
                'success' => false,
                'error' => 'Medicamento no encontrado'
            ], 404);
        }

        $medicamento->delete();

        return response()->json([
            'success' => true,
            'message' => 'Medicamento eliminado correctamente'
        ]);
    }
}