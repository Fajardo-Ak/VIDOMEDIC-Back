<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TratamientoMedicamento extends Model
{
    protected $fillable = [
        'tratamiento_id', 'medicamento_id', 
        'cantidad_por_toma', 'instrucciones'
    ];

    // Relación con Tratamiento
    public function tratamiento()
    {
        return $this->belongsTo(Tratamiento::class);
    }

    // Relación con Medicamento
    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class);
    }

    // Relación con DosisProgramadas
    public function dosisProgramadas()
    {
        return $this->hasMany(DosisProgramada::class);
    }
}