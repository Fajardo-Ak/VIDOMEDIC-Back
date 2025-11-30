<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\MedicamentoController;
use App\Http\Controllers\TratamientoController;
use App\Http\Controllers\DosisController;
use App\Http\Controllers\SupersetController;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

///RUTAS PUBLICAS///--------------------------------------------------------------------------------------------------------
Route::post('registro', [UsuarioController::class,'registro']);
Route::post('login', [UsuarioController::class,'login']);
///SECCION ACTULIZACION
Route::get('/actualizaciones', [ContactoController::class, 'actualizaciones']);
//rutas para diversos providers
// OAuth Routes
Route::get('/superset/guest-token', [SupersetController::class, 'getGuestToken']);

// Ruta para que un servicio externo active el cron gratis
Route::get('/cron-dosis-run', function () {
    Artisan::call('schedule:run');
    return 'Cron ejecutado: ' . Artisan::output();
});

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

///RUTAS PROTEGIDAS///------------------------------------------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
///CERRAR SESION///---------------------------------------------------------------------------------------------------------
    Route::post('/logout', [UsuarioController::class, 'logout']);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

///INICIO RUTAS///----------------------------------------------------------------------------------------------------------
    //Route::apiResource('inicio', ContactoController::class); nose que hace :u
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

///NOTIFICACION RUTA///---------------------------------------------------------------------------------------------------------
    Route::post('/notifications/subscribe', function (Request $request) {
        $request->validate([
            'endpoint'    => 'required',
            'keys.auth'   => 'required',
            'keys.p256dh' => 'required',
        ]);

        $endpoint = $request->endpoint;
        $token = $request->keys['auth'];
        $key = $request->keys['p256dh'];

        $request->user()->updatePushSubscription($endpoint, $key, $token);

        return response()->json(['success' => true]);
    });
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

///MEDICAMENTOS RUTAS///----------------------------------------------------------------------------------------------------
    Route::get('/medicamentos/buscar', [MedicamentoController::class, 'buscar']); // ACTUALIZADA
    Route::get('/medicamentos/mas-usados', [MedicamentoController::class, 'masUsados']); // NUEVA
    Route::get('/medicamentos', [MedicamentoController::class, 'index']);
    Route::post('/medicamentos', [MedicamentoController::class, 'store']);
    Route::get('/medicamentos/{id}', [MedicamentoController::class, 'show']);
    Route::put('/medicamentos/{id}', [MedicamentoController::class, 'update']);
    Route::delete('/medicamentos/{id}', [MedicamentoController::class, 'destroy']);
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

///CONFIGURACIONES RUTAS///------------------------------------------------------------------------------------------------
    ///SECCION CUENTA
    Route::get('/usuario/perfil', [UsuarioController::class, 'obtenerPerfil']);
    Route::put('/usuario/perfil', [UsuarioController::class, 'editarPerfil']);
    Route::put('/usuario/password', [UsuarioController::class, 'cambiarPassword']);
    Route::post('/usuario/foto', [UsuarioController::class, 'subirFoto']);
    ///SECCION CONTACTOS
    Route::get('/contactos', [ContactoController::class, 'index']);
    Route::post('/contactos', [ContactoController::class, 'store']);
    Route::put('/contactos/{id}', [ContactoController::class, 'update']);
    Route::delete('/contactos/{id}', [ContactoController::class, 'destroy']);
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

///TRATAMIENTO RUTAS///----------------------------------------------------------------------------------------------------
    Route::get('/tratamientos/verificar-activo', [TratamientoController::class, 'verificarActivo']);
    Route::get('/tratamientos', [TratamientoController::class, 'index']);
    Route::post('/tratamientos', [TratamientoController::class, 'store']);
    Route::get('/tratamientos/{id}/pdf', [TratamientoController::class, 'generarPdf']); //para buscar datos y generar el documento
    Route::get('/tratamientos/{id}', [TratamientoController::class, 'show']);
    Route::put('/tratamientos/{id}', [TratamientoController::class, 'update']);
    Route::delete('/tratamientos/{id}', [TratamientoController::class, 'destroy']);
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

///DOSIS RUTAS///----------------------------------------------------------------------------------------------------------
    Route::get('/dosis/agenda-semanal', [DosisController::class, 'agendaSemanal']); // NUEVA
    Route::get('/dosis/agenda-mensual', [DosisController::class, 'agendaMensual']); // NUEVA
    Route::get('/dosis/pendientes-hoy', [DosisController::class, 'pendientesHoy']); // NUEVA
    Route::get('/dosis/proximas', [DosisController::class, 'proximasDosis']); // NUEVA
    Route::get('/dosis/estadisticas-adherencia', [DosisController::class, 'estadisticasAdherencia']); // NUEVA
    Route::put('/dosis/{id}/marcar', [DosisController::class, 'marcarDosis']); // ACTUALIZADA
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

///AGENDA RUTAS///---------------------------------------------------------------------------------------------------------
    // Estas rutas las mantenemos por compatibilidad, pero ahora usan DosisController
    Route::get('/agenda/semana', [DosisController::class, 'agendaSemanal']); // ACTUALIZADA
    Route::get('/agenda/ames', [DosisController::class, 'agendaMensual']); // ACTUALIZADA
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
});