<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Medicamento;
use Illuminate\Support\Facades\Auth;

class MedicamentoController extends Controller
{
    public function index()
    {
        $usuarioId = Auth::id();
        $medicamentos = Medicamento::where('usuario_id', $usuarioId)->get();
        
        return response()->json([
            'success' => true,
            'data' => $medicamentos
        ]);
    }

    public function store(Request $req)
    {
        $req->validate([
            'nombre' => 'required|string|max:100',
            'dosis' => 'required|string|max:50',
            'notas_opcionales' => 'nullable|string'
        ]);

        $medicamento = new Medicamento;
        $medicamento->usuario_id = Auth::id();
        $medicamento->nombre = $req->input('nombre');
        $medicamento->dosis = $req->input('dosis');
        $medicamento->notas_opcionales = $req->input('notas_opcionales');
        $medicamento->save();

        return response()->json([
            'success' => true,
            'data' => $medicamento
        ], 201);
    }

    public function update(Request $req, $id)
    {
        $req->validate([
            'nombre' => 'required|string|max:100',
            'dosis' => 'required|string|max:50',
            'notas_opcionales' => 'nullable|string'
        ]);

        $medicamento = Medicamento::where('usuario_id', Auth::id())
                                ->where('id', $id)
                                ->firstOrFail();

        $medicamento->nombre = $req->input('nombre');
        $medicamento->dosis = $req->input('dosis');
        $medicamento->notas_opcionales = $req->input('notas_opcionales');
        $medicamento->save();

        return response()->json([
            'success' => true,
            'data' => $medicamento
        ]);
    }

    public function destroy($id)
    {
        $medicamento = Medicamento::where('usuario_id', Auth::id())
                                ->where('id', $id)
                                ->firstOrFail();

        $medicamento->delete();

        return response()->json([
            'success' => true,
            'message' => 'Medicamento eliminado correctamente'
        ]);
    }
}
