<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tratamiento extends Model
{
    protected $fillable = [
        'usuario_id', 'nombre_tratamiento', 'fecha_inicio', 'fecha_fin', 
        'estado', 'notas'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    // Relación con Usuario (asumiendo que tu modelo se llama User)
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    // Relación con DetalleTratamiento (nombre corregido)
    public function detalleTratamientos()
    {
        return $this->hasMany(DetalleTratamiento::class, 'tratamiento_id');
    }

    // Relación directa con Medicamentos a través de DetalleTratamiento
    public function medicamentos()
    {
        return $this->hasManyThrough(
            Medicamento::class,
            DetalleTratamiento::class,
            'tratamiento_id', // FK en DetalleTratamiento
            'id', // FK en Medicamento
            'id', // Local key en Tratamiento
            'medicamento_id' // Local key en DetalleTratamiento
        );
    }
}