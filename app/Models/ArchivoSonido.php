<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivoSonido extends Model
{
    protected $table = 'archivos_sonido';
    
    protected $fillable = [
        'nombre_archivo',
        'url',
        'tipo_archivo'
    ];
}

