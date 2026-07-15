<?php

namespace App\Modules\Auth\Entities;

use MongoDB\Laravel\Eloquent\Model;

class CiudadanoPerfil extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'ciudadano_perfiles';

    protected $fillable = [
        'usuario_uuid',
        'prefijo_celular',
        'codigo_pais',
        'ubicacion',
    ];

    protected $casts = [
        'ubicacion' => 'array',
    ];
}
