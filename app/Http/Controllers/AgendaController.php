<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agenda;
use App\Models\Medicamento;
use App\Models\ArchivoSonido;
use Illuminate\Support\Facades\Auth;

class AgendaController extends Controller
{
    public function index()
    {
        $usuarioId = Auth::id();
        
        $agendas = Agenda::with(['medicamento', 'sonido'])
                        ->whereHas('medicamento', function($query) use ($usuarioId) {
                            $query->where('usuario_id', $usuarioId);
                        })
                        ->get();
        
        return response()->json([
            'success' => true,
            'data' => $agendas
        ]);
    }

    public function store(Request $req)
    {
        $req->validate([
            'medicamento_id' => 'required|integer|exists:medicamentos,id',
            'dia_semana' => 'required|in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo',
            'hora' => 'required|date_format:H:i',
            'sonido_id' => 'nullable|integer|exists:archivos_sonido,id',
            'recordatorio_activo' => 'boolean'
        ]);

        // Verificar que el medicamento pertenece al usuario
        $medicamento = Medicamento::where('usuario_id', Auth::id())
                                 ->where('id', $req->medicamento_id)
                                 ->firstOrFail();

        $agenda = new Agenda;
        $agenda->medicamento_id = $req->input('medicamento_id');
        $agenda->dia_semana = $req->input('dia_semana');
        $agenda->hora = $req->input('hora');
        $agenda->sonido_id = $req->input('sonido_id');
        $agenda->recordatorio_activo = $req->input('recordatorio_activo', true);
        $agenda->save();

        return response()->json([
            'success' => true,
            'data' => $agenda
        ], 201);
    }

    public function update(Request $req, $id)
    {
        $req->validate([
            'dia_semana' => 'in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo',
            'hora' => 'date_format:H:i',
            'sonido_id' => 'nullable|integer|exists:archivos_sonido,id',
            'recordatorio_activo' => 'boolean'
        ]);

        $agenda = Agenda::with(['medicamento'])
                       ->whereHas('medicamento', function($query) {
                           $query->where('usuario_id', Auth::id());
                       })
                       ->where('id', $id)
                       ->firstOrFail();

        if ($req->has('dia_semana')) {
            $agenda->dia_semana = $req->input('dia_semana');
        }
        
        if ($req->has('hora')) {
            $agenda->hora = $req->input('hora');
        }
        
        if ($req->has('sonido_id')) {
            $agenda->sonido_id = $req->input('sonido_id');
        }
        
        if ($req->has('recordatorio_activo')) {
            $agenda->recordatorio_activo = $req->input('recordatorio_activo');
        }
        
        $agenda->save();

        return response()->json([
            'success' => true,
            'data' => $agenda
        ]);
    }

    public function destroy($id)
    {
        $agenda = Agenda::with(['medicamento'])
                       ->whereHas('medicamento', function($query) {
                           $query->where('usuario_id', Auth::id());
                       })
                       ->where('id', $id)
                       ->firstOrFail();

        $agenda->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recordatorio eliminado correctamente'
        ]);
    }
}
