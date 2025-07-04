<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Administrador;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; //la autentificacion
use Illuminate\Support\Str;

class AdminController extends Controller
{
    //funcion de registro
    function registro(Request $req)
    {
        $req->validate([
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|unique:administradores,correo',
            'contraseña' => 'required|min:1',
            'imagen'=> 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        
         
        //se crea un nuevo administrador
        $admin = new Administrador;
        $admin->nombre= $req->input('nombre');
        $admin->correo= $req->input('correo');
        $admin->contraseña= Hash::make($req->input('contraseña'));
        //subir la imagen si existe
        if($req->hasFile('imagen') && $req->file('imagen')->isValid()){
            $imagenPath = $req->file('imagen')->store('imagenes', 'public');
            //$imagenPaht = $req->file('imagen'->store('imagenes','public'));
            $admin->imagen = 'storage/' . $imagenPath; //guarda la url
        } else{
            $admin->imagen = null;
        }
        $admin->save();
        $token = $admin->id . '_' . Str::random(40);
        return $admin;
    }

    //funcion de login
    public function login(Request $req)
    {
        //validar las credenciales
        $credentials = $req->only('correo','contraseña');
        //buscar el usuario en la base de datos
        $admin = Administrador::where('correo', $credentials['correo'])->first();
        //verificar que el administrador existe y que la contraseña es correcta
        if($admin && Hash::check($credentials['contraseña'], $admin->contraseña)){
            return response()->json([
                'success'=> true,
                'user'=> $admin,
                
            ]);
        } else{
            //si las credenciales son incorrectas
            return response()->json([
                'success'=>false,
                'message'=>'Credenciales incorrectas',
            ], 400);
        }
    }
}
