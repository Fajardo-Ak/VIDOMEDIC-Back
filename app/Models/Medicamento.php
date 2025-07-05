<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicamento extends Model
{
    protected $table = 'medicamentos';
    
    protected $fillable = [
        'usuario_id',
        'nombre',
        'dosis',
        'notas_opcionales'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function agenda()
    {
        return $this->hasMany(Agenda::class);
    }
}
