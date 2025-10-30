<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tratamiento extends Model
{
    protected $fillable = [
        'usuario_id', 'fecha_inicio', 'fecha_fin', 
        'frecuencia', 'importancia', 'notas'
    ];

    protected $casts = [
        'frecuencia' => 'array',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'date',
    ];

    // Relación con Usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    // Relación con TratamientoMedicamento
    public function tratamientoMedicamentos()
    {
        return $this->hasMany(TratamientoMedicamento::class);
    }

    // Relación directa con Medicamentos a través de TratamientoMedicamento
    public function medicamentos()
    {
        return $this->hasManyThrough(
            Medicamento::class,
            TratamientoMedicamento::class,
            'tratamiento_id', // FK en TratamientoMedicamento
            'id', // FK en Medicamento
            'id', // Local key en Tratamiento
            'medicamento_id' // Local key en TratamientoMedicamento
        );
    }
}