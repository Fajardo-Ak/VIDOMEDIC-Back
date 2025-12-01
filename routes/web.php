<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use Minishlink\WebPush\Vapid; // Importante
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/




Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/{provider}/redirect', [UsuarioController::class, 'redirectToProvider']);
Route::get('/auth/{provider}/callback', [UsuarioController::class, 'handleProviderCallback']);


Route::get('/generar-llaves', function () {
    return Vapid::createVapidKeys(); 
});

// Ruta DE EMERGENCIA para limpiar caché en Render (Muy importante)
Route::get('/limpiar-todo', function () {
    Artisan::call('optimize:clear');
    return 'Caché borrada. Ahora Render debería ver tus nuevas rutas.';
});

Route::get('/cron-dosis-run', function () {
    // Llamamos directo al comando (sin schedule) para ver los textos en pantalla
    try {
        Artisan::call('dosis:enviar');
        return nl2br(Artisan::output()); 
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});