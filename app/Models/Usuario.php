<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    //Se espesifica el nombre de la tabla en la base de datos
    protected $table = 'usuarios';
    //si la clave primaria no es id se espesifica aqui si es nesesario
    //protected $primaryKey = 'id';
    //para que laravel no se protega de asignaciones masivas definimos los campos permitidos
    protected $fillable = [
        'nombre',
        'correo',
        'password',
        //'rol',
        //'empresa_id',
    ];

    // Desactiva timestamps automáticos porque usas creado_en
    public $timestamps = false;
    
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relación con empresa 
    //public function empresa()
    //{
        //return $this->belongsTo(Empresa::class, 'empresa_id');
    //}
}