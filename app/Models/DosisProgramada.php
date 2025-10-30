<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DosisProgramada extends Model
{
    protected $fillable = [
        'tratamiento_medicamento_id', 'fecha_hora', 'tomada'
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'tomada' => 'boolean',
    ];

    // Relación con TratamientoMedicamento
    public function tratamientoMedicamento()
    {
        return $this->belongsTo(TratamientoMedicamento::class);
    }

    // Acceso directo al medicamento
    public function medicamento()
    {
        return $this->hasOneThrough(
            Medicamento::class,
            TratamientoMedicamento::class,
            'id', // FK en TratamientoMedicamento
            'id', // FK en Medicamento
            'tratamiento_medicamento_id', // Local key en DosisProgramada
            'medicamento_id' // Local key en TratamientoMedicamento
        );
    }
}