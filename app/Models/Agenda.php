<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    protected $table = 'agenda';
    
    protected $fillable = [
        'medicamento_id',
        'dia_semana',
        'hora',
        'sonido_id',
        'recordatorio_activo'
    ];

    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class);
    }

    public function sonido()
    {
        return $this->belongsTo(ArchivoSonido::class, 'sonido_id');
    }

    public function historial()
    {
        return $this->hasMany(HistorialRecordatorio::class, 'agenda_id');
    }
}
