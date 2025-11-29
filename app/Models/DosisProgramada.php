<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DosisProgramada extends Model
{
    protected $table = 'dosis_programadas'; // Especificar tabla

    protected $fillable = [
        'tratamiento_medicamento_id', 'fecha_hora', 'fecha_hora_tomada',
        'estado', 'tomada', 'notas_toma'
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'fecha_hora_tomada' => 'datetime',
        'tomada' => 'boolean',
    ];

    // Relación con DetalleTratamiento (CORREGIDA)
    public function detalleTratamiento()
    {
        return $this->belongsTo(DetalleTratamiento::class, 'tratamiento_medicamento_id');
    }

    // Relación con Medicamento a través de DetalleTratamiento
    public function medicamento()
    {
        return $this->hasOneThrough(
            Medicamento::class,
            DetalleTratamiento::class,
            'id', // FK en DetalleTratamiento
            'id', // FK en Medicamento  
            'tratamiento_medicamento_id', // Local key en DosisProgramada
            'medicamento_id' // Local key en DetalleTratamiento
        );
    }
}