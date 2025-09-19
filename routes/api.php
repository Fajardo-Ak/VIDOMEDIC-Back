<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\MedicamentoController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\HistorialRecordatorioController;

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

//----------------------------------------------------------------------------------------------------------------------------
Route::post('registro', [UsuarioController::class,'registro']);
Route::post('login', [UsuarioController::class,'login']);
//---------------------------------------------------------------------------------------------------------------------------
//rutas protegidas----------------------------------------------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    //inicio
    //Route::apiResource('inicio', ContactoController::class);
    // Contactos
    Route::apiResource('contactos', ContactoController::class);
    
    // Medicamentos
    Route::apiResource('medicamentos', MedicamentoController::class);
    
    // Agenda
    Route::apiResource('agenda', AgendaController::class);
    
    // Historial de recordatorios
    Route::get('agenda/{agendaId}/historial', [HistorialRecordatorioController::class, 'index']);
    Route::post('agenda/{agendaId}/historial', [HistorialRecordatorioController::class, 'store']);
});
