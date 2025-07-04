<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Administrador extends Model
{
    use HasFactory;
    //Se espesifica el nombre de la tabla en la base de datos
    protected $table = 'administradores';
    //si la clave primaria no es id se espesifica aqui si es nesesario
    //protected $primaryKey = 'id';
    //para que laravel no se protega de asignaciones masivas definimos los campos permitidos
    protected $fillable = [
        'nombre',
        'correo',
        'contraseña',
        'imagen'
    ];

    public $timestamps = false;
}
