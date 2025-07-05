<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    protected $table = 'contactos';
    
    protected $fillable = [
        'usuario_id',
        'nombre_contacto',
        'telefono',
        'parentesco'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
