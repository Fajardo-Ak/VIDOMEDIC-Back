<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contactos extends Model
{
    use HasFactory;

    protected $table = 'contactos';

    protected $fillable = [
        'usuario_id',
        'nombre_contacto', 
        'telefono',
        'correo'
    ];

    // Relación con Usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}