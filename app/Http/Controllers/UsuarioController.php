<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class UsuarioController extends Controller
{
    // funcion de registro (misma lógica)
    function registro(Request $req)
    {
        $req->validate([
            'nombre' => 'required|string|max:100',
            'correo' => 'required|email|unique:usuarios,correo',
            'password' => 'required|min:1',
        ]);

        $usuario = new Usuario;
        $usuario->nombre   = $req->input('nombre');
        $usuario->correo   = $req->input('correo');
        $usuario->password = Hash::make($req->input('password'));
        $usuario->save();

        // mantengo tu token manual (aunque no lo uses)
        $token = $usuario->id . '_' . Str::random(40);

        return $usuario;
    }

    // funcion de login (misma estructura)
    public function login(Request $req)
    {
        $credentials = $req->only('correo','password');
        $usuario = Usuario::where('correo', $credentials['correo'])->first();

        if ($usuario && Hash::check($credentials['password'], $usuario->password)) {
            $token = $usuario->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success'=> true,
                'correo'=> $usuario,
                'token'=> $token
            ]);
        }

        return response()->json([
            'success'=>false,
            'message'=>'Credenciales incorrectas',
        ], 400);
    }

    // 1. Obtener perfil (igual)
    public function obtenerPerfil()
    {
        $usuarioId = Auth::id();
        $usuario = Usuario::find($usuarioId);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'correo' => $usuario->correo,
                'foto_perfil' => $usuario->foto_perfil
            ]
        ]);
    }

    // 2. Editar perfil (igual)
    public function editarPerfil(Request $req)
    {
        $usuarioId = Auth::id();
        $usuario = Usuario::find($usuarioId);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        $req->validate([
            'nombre' => 'sometimes|string|max:100',
            'correo' => 'sometimes|email|unique:usuarios,correo,' . $usuarioId
        ]);

        if ($req->has('nombre')) $usuario->nombre = $req->nombre;
        if ($req->has('correo')) $usuario->correo = $req->correo;

        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
            'usuario' => [
                'nombre' => $usuario->nombre,
                'correo' => $usuario->correo
            ]
        ]);
    }

    // 3. Cambiar contraseña (igual)
    public function cambiarPassword(Request $req)
    {
        $usuarioId = Auth::id();
        $usuario = Usuario::find($usuarioId);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        $req->validate([
            'password_actual' => 'required',
            'nueva_password' => 'required|min:1'
        ]);

        if (!Hash::check($req->password_actual, $usuario->password)) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña actual es incorrecta'
            ], 400);
        }

        $usuario->password = Hash::make($req->nueva_password);
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña cambiada correctamente'
        ]);
    }

    // 4. Subir/actualizar foto (igual)
    public function subirFoto(Request $req)
    {
        $usuarioId = Auth::id();
        $usuario = Usuario::find($usuarioId);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        $req->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($usuario->foto_perfil && file_exists(public_path($usuario->foto_perfil))) {
            @unlink(public_path($usuario->foto_perfil));
        }

        $carpetaDestino = public_path('uploads/fotos_perfil');
        if (!file_exists($carpetaDestino)) {
            @mkdir($carpetaDestino, 0755, true);
        }

        $extension = $req->file('foto')->getClientOriginalExtension();
        $nombreArchivo = time() . '_' . uniqid() . '.' . $extension;
        $rutaRelativa = 'uploads/fotos_perfil/' . $nombreArchivo;

        $req->file('foto')->move($carpetaDestino, $nombreArchivo);

        if (!file_exists(public_path($rutaRelativa))) {
            return response()->json([
                'success' => false,
                'error' => 'Error al guardar el archivo'
            ], 500);
        }

        $usuario->foto_perfil = $rutaRelativa;
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto de perfil actualizada',
            'foto_perfil' => $rutaRelativa
        ]);
    }

    /* Redirige al proveedor OAuth (google, microsoft)*/
    public function redirectToProvider(string $provider)
    {
        $allowed = ['google', 'microsoft'];

        if (!in_array($provider, $allowed)) {
            return response()->json(['error' => 'Provider not allowed'], 400);
        }

        try {
            $config = [];

            if ($provider === 'google') {
                $config = [
                    'redirect_uri' => config('services.google.redirect'),
                    'access_type'  => 'offline',
                    'prompt'       => 'consent select_account'
                ];
            } elseif ($provider === 'microsoft') {
                $config = [
                    'redirect_uri' => config('services.microsoft.redirect'),
                    'prompt'       => 'select_account'
                    // 'scope' => 'User.Read', // si lo necesitas
                ];
            }

            $url = Socialite::driver($provider)
                ->stateless()
                ->with($config)
                ->redirect()
                ->getTargetUrl();

            return redirect($url);

        } catch (\Throwable $e) {
            Log::error('Social redirect error', [
                'provider' => $provider,
                'message'  => $e->getMessage(),
            ]);

            return response()->json([
                'error'    => 'Error generating redirect URL',
                'message'  => $e->getMessage(),
                'provider' => $provider
            ], 500);
        }
    }

    public function handleProviderCallback(string $provider)
    {
        Log::info('=== CALLBACK INICIADO ===', ['provider' => $provider]);

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();

            Log::info('Datos recibidos Socialite', [
                'email' => $socialUser->getEmail(),
                'name'  => $socialUser->getName(),
                'id'    => $socialUser->getId(),
            ]);

            // Mantengo tu "forzar creación" + manejo de duplicado
            try {
                $findUser = Usuario::create([
                    'nombre'      => $socialUser->getName() ?? $socialUser->getEmail(),
                    'correo'      => $socialUser->getEmail(),
                    'password'    => Hash::make(Str::random(40)),
                    'provider'    => $provider,
                    'provider_id' => $socialUser->getId(),
                    'foto_perfil' => $socialUser->getAvatar() ?? 'default.png'
                ]);

                Log::info('USUARIO CREADO FORZADAMENTE', ['id' => $findUser->id]);

            } catch (\Throwable $dup) {
                Log::warning('Usuario ya existía, recuperando', ['err' => $dup->getMessage()]);

                $findUser = Usuario::where('provider', $provider)
                    ->where('provider_id', $socialUser->getId())
                    ->first();

                if (!$findUser && $socialUser->getEmail()) {
                    $findUser = Usuario::where('correo', $socialUser->getEmail())->first();

                    if (!$findUser) {
                        $findUser = Usuario::updateOrCreate(
                            ['correo' => $socialUser->getEmail()],
                            [
                                'nombre'      => $socialUser->getName() ?? $socialUser->getEmail(),
                                'password'    => Hash::make(Str::random(40)),
                                'provider'    => $provider,
                                'provider_id' => $socialUser->getId(),
                                'foto_perfil' => $socialUser->getAvatar() ?? 'default.png'
                            ]
                        );
                    }
                }
            }

            $token = $findUser->createToken('auth_token')->plainTextToken;

            $front = rtrim(env('FRONTEND_OAUTH_REDIRECT', 'http://localhost:3001/oauth/callback'), '/');
            return redirect()->away($front . '?token=' . urlencode($token));

        } catch (\Throwable $e) {
            Log::error('ERROR CRÍTICO CALLBACK', [
                'provider' => $provider,
                'message'  => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            $login = rtrim(env('FRONTEND_LOGIN', 'http://localhost:3001/login'), '/');
            return redirect()->away($login . '?error=creation_failed');
        }
    }
}
