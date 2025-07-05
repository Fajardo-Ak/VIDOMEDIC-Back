<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HistorialRecordatorio;
use App\Models\Agenda;
use Illuminate\Support\Facades\Auth;

class HistorialRecordatorioController extends Controller
{
    public function index($agendaId)
    {
        $historial = HistorialRecordatorio::with(['agenda.medicamento'])
                                        ->whereHas('agenda.medicamento', function($query) {
                                            $query->where('usuario_id', Auth::id());
                                        })
                                        ->where('agenda_id', $agendaId)
                                        ->get();
        
        return response()->json([
            'success' => true,
            'data' => $historial
        ]);
    }

    public function store(Request $req, $agendaId)
    {
        $req->validate([
            'estado' => 'required|in:enviado,fallido,visto,ignorado',
            'comentario' => 'nullable|string'
        ]);

        // Verificar que la agenda pertenece al usuario
        $agenda = Agenda::with(['medicamento'])
                       ->whereHas('medicamento', function($query) {
                           $query->where('usuario_id', Auth::id());
                       })
                       ->where('id', $agendaId)
                       ->firstOrFail();

        $historial = new HistorialRecordatorio;
        $historial->agenda_id = $agendaId;
        $historial->estado = $req->input('estado');
        $historial->comentario = $req->input('comentario');
        $historial->save();

        return response()->json([
            'success' => true,
            'data' => $historial
        ], 201);
    }
}
