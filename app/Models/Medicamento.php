<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Medicamento extends Model
{
    use HasFactory;
    protected $table = 'medicamentos';
    
    protected $fillable = [
        'usuario_id',
        'nombre',
        'via_administracion',
        'via_administracion_perzonalizada',
        'dosis',
        'importancia'
    ];

    protected $attributes = [
        'importancia' => 'Baja'
    ];

    //relacion con usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
