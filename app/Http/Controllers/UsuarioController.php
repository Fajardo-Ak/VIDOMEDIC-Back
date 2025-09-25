<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; //la autentificacion
use Illuminate\Support\Str;

class UsuarioController extends Controller
{
    //funcion de registro
    function registro(Request $req)
    {
        $req->validate([
            'nombre' => 'required|string|max:100',
            'correo' => 'required|email|unique:usuarios,correo',
            'password' => 'required|min:1',
            //'rol' => 'in:usuario,empresa',
            //'empresa_id' => 'nullable|integer|exists:empresas,id',
        ]);
        
         
        //se crea un nuevo Usuario
        $usuario = new Usuario;
        $usuario->nombre= $req->input('nombre');
        $usuario->correo= $req->input('correo');
        $usuario->password= Hash::make($req->input('password'));
        //$usuario->rol= $req->rol ?? 'usuario',
        //$usuario->empresa_id= $req->input(('empresa_id,'));
        $usuario->save();
        $token = $usuario->id . '_' . Str::random(40);
        return $usuario;
    }

    //funcion de login
    public function login(Request $req)
    {
        //validar las credenciales
        $credentials = $req->only('correo','password');
        //buscar el usuario en la base de datos
        $usuario = Usuario::where('correo', $credentials['correo'])->first();
        //verificar que el administrador existe y que la contraseña es correcta
        if($usuario&& Hash::check($credentials['password'], $usuario->password)){
            //Generacion de token con Sanctum
            $token = $usuario->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success'=> true,
                'correo'=> $usuario,
                'token'=> $token
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
