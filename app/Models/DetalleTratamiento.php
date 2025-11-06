<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleTratamiento extends Model
{
    protected $table = 'detalle_tratamiento'; // Especificar tabla

    protected $fillable = [
        'tratamiento_id', 'medicamento_id', 'tipo_frecuencia',
        'valor_frecuencia', 'dias_semana', 'horarios_fijos',
        'cantidad_por_toma', 'instrucciones'
    ];

    protected $casts = [
        'dias_semana' => 'array',
        'horarios_fijos' => 'array',
    ];

    // Relación con Tratamiento
    public function tratamiento()
    {
        return $this->belongsTo(Tratamiento::class, 'tratamiento_id');
    }

    // Relación con Medicamento
    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class, 'medicamento_id');
    }

    // Relación con DosisProgramadas (CORREGIDA)
    public function dosisProgramadas()
    {
        return $this->hasMany(DosisProgramada::class, 'tratamiento_medicamento_id');
    }
}