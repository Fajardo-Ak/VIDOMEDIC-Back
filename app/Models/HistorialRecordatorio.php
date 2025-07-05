<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialRecordatorio extends Model
{
    protected $table = 'historial_recordatorios';
    
    protected $fillable = [
        'agenda_id',
        'estado',
        'comentario'
    ];

    public function agenda()
    {
        return $this->belongsTo(Agenda::class);
    }
}
