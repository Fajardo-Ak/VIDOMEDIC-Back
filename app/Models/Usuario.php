<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

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
        'foto_perfil',
        'provider',
        'provider_id',
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

    public function getFotoPerfilAttribute($value)
    {
        // $value es el dato crudo de la base de datos

        // 1. Si el valor es una URL absoluta (de Google/Microsoft), devuélvela tal cual.
        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        // 2. Si el valor es una ruta local (de 'subirFoto'), constrúyele la URL.
        if ($value) {
            // URL::asset() crea la URL completa: http://localhost:8000/uploads/fotos/foto.jpg
            return URL::asset($value);
        }

        // 3. Si no hay foto (es null), devuelve null o una imagen default.
        return null;
        // O si prefieres un default:
        // return 'https://www.tu-sitio.com/images/avatar_default.png';
    }
}