<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contactos;
use Illuminate\Support\Facades\Auth;

class ContactoController extends Controller
{
    //ENLISTA LOS CONTACTOS DEL USUARIO
    public function index()
    {
        $usuarioId = Auth::id();
        $contactos = Contactos::where('usuario_id', $usuarioId)
                                ->orderBy('created_at','desc')
                                ->get();
        
        return response()->json([
            'success' => true,
            'data' => $contactos
        ]);
    }

    //FUNCION PARA CREAR NUEVOS CONTACTOS (CON LIMITE DE 3)
    public function store(Request $req)
    {
        $usuarioId = Auth::id();
        // Validar datos
        $req->validate([
            'nombre_contacto' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'correo' => 'nullable|email|max:255'
        ]);

        // Verificar límite de 3 contactos
        $totalContactos = Contactos::where('usuario_id', $usuarioId)->count();
        if ($totalContactos >= 3) {
            return response()->json([
                'success' => false,
                'error' => 'Solo puedes tener máximo 3 contactos'
            ], 400);
        }

        // Crear contacto
        $contacto = new Contactos;
        $contacto->usuario_id = $usuarioId;
        $contacto->nombre_contacto = $req->nombre_contacto;
        $contacto->telefono = $req->telefono;
        $contacto->correo = $req->correo;
        $contacto->save();

        return response()->json([
            'success' => true,
            'message' => 'Contacto creado correctamente',
            'data' => $contacto
        ], 201);
    }

    //FUNCION PARA ACTUALIZAR DATOS DEL CONTACTO
    public function update(Request $req, $id)
    {
        $usuarioId = Auth::id();
        // Validar datos
        $req->validate([
            'nombre_contacto' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'correo' => 'nullable|email|max:255'
        ]);

        // Buscar contacto del usuario
        $contacto = Contactos::where('usuario_id', $usuarioId)->find($id);

        if (!$contacto) {
            return response()->json([
                'success' => false,
                'error' => 'Contacto no encontrado'
            ], 404);
        }

        // Actualizar contacto
        $contacto->nombre_contacto = $req->nombre_contacto;
        $contacto->telefono = $req->telefono;
        $contacto->correo = $req->correo;
        $contacto->save();

        return response()->json([
            'success' => true,
            'message' => 'Contacto actualizado correctamente',
            'data' => $contacto
        ]);
    }

    //FUNCION DE ELIMINAR CONTACTO
    public function destroy($id)
    {
        $usuarioId = Auth::id();
        // Buscar contacto del usuario
        $contacto = Contactos::where('usuario_id', $usuarioId)->find($id);

        if (!$contacto) {
            return response()->json([
                'success' => false,
                'error' => 'Contacto no encontrado'
            ], 404);
        }

        $contacto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contacto eliminado correctamente'
        ]);
    }

    //FUNCIÓN DE MENSAJE ACTILIZACION DE DATOS
    public function actualizaciones()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'version' => '1.0.2',
                'fecha' => '2025-10-03',
                'cambios' => [
                    '✓ Sistema completo de contactos de emergencia',
                    '✓ Gestión de perfil de usuario mejorada', 
                    '✓ Subida de fotos de perfil',
                    '✓ Interfaz de configuración renovada',
                    '✓ Correcciones de estabilidad general'
                ]
            ]
        ]);
    }
}
