<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    use HasFactory;
    //Se espesifica el nombre de la tabla en la base de datos
    protected $table = 'usuarios';
    //si la clave primaria no es id se espesifica aqui si es nesesario
    //protected $primaryKey = 'id';
    //para que laravel no se protega de asignaciones masivas definimos los campos permitidos
    protected $fillable = [
        'nombre',
        'correo',
        'contraseña',
        //'rol',
        //'empresa_id',
    ];

    // Desactiva timestamps automáticos porque usas creado_en
    public $timestamps = false;

    // Relación con empresa 
    //public function empresa()
    //{
        //return $this->belongsTo(Empresa::class, 'empresa_id');
    //}
}