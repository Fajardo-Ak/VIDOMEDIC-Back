<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contacto;
use Illuminate\Support\Facades\Auth;

class ContactoController extends Controller
{
    public function index()
    {
        $usuarioId = Auth::id();
        $contactos = Contacto::where('usuario_id', $usuarioId)->get();
        
        return response()->json([
            'success' => true,
            'data' => $contactos
        ]);
    }

    public function store(Request $req)
    {
        $req->validate([
            'nombre_contacto' => 'required|string|max:100',
            'telefono' => 'required|string|max:20',
            'parentesco' => 'nullable|string|max:50'
        ]);

        $contacto = new Contacto;
        $contacto->usuario_id = Auth::id();
        $contacto->nombre_contacto = $req->input('nombre_contacto');
        $contacto->telefono = $req->input('telefono');
        $contacto->parentesco = $req->input('parentesco');
        $contacto->save();

        return response()->json([
            'success' => true,
            'data' => $contacto
        ], 201);
    }

    public function update(Request $req, $id)
    {
        $req->validate([
            'nombre_contacto' => 'required|string|max:100',
            'telefono' => 'required|string|max:20',
            'parentesco' => 'nullable|string|max:50'
        ]);

        $contacto = Contacto::where('usuario_id', Auth::id())
                          ->where('id', $id)
                          ->firstOrFail();

        $contacto->nombre_contacto = $req->input('nombre_contacto');
        $contacto->telefono = $req->input('telefono');
        $contacto->parentesco = $req->input('parentesco');
        $contacto->save();

        return response()->json([
            'success' => true,
            'data' => $contacto
        ]);
    }

    public function destroy($id)
    {
        $contacto = Contacto::where('usuario_id', Auth::id())
                          ->where('id', $id)
                          ->firstOrFail();

        $contacto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contacto eliminado correctamente'
        ]);
    }
}
