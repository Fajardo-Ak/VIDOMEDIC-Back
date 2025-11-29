<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Medicamento extends Model
{
    use HasFactory;
    
    protected $table = 'medicamentos';
    
    protected $fillable = [
        'usuario_id', 'nombre', 'via_administracion', 
        'via_administracion_personalizada', 'presentacion',
        'importancia', 'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'importancia' => 'string'
    ];

    // Relación con DetalleTratamiento (actualizada)
    public function detalleTratamientos()
    {
        return $this->hasMany(DetalleTratamiento::class, 'medicamento_id');
    }

    // Relación con tratamientos a través de detalleTratamientos
    public function tratamientos()
    {
        return $this->hasManyThrough(
            Tratamiento::class,
            DetalleTratamiento::class,
            'medicamento_id', // FK en DetalleTratamiento
            'id', // FK en Tratamiento  
            'id', // Local key en Medicamento
            'tratamiento_id' // Local key en DetalleTratamiento
        );
    }
}
