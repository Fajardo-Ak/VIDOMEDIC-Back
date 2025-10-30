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
        'via_administracion_personalizada', 'presentacion'
    ];

    // Relación con TratamientoMedicamento
    public function tratamientoMedicamentos()
    {
        return $this->hasMany(TratamientoMedicamento::class);
    }

    // Relación directa con Tratamientos a través de TratamientoMedicamento
    public function tratamientos()
    {
        return $this->hasManyThrough(
            Tratamiento::class,
            TratamientoMedicamento::class,
            'medicamento_id', // FK en TratamientoMedicamento
            'id', // FK en Tratamiento
            'id', // Local key en Medicamento
            'tratamiento_id' // Local key en TratamientoMedicamento
        );
    }
}

