<?php

namespace App\Modules\Incidencias\Entities;

use MongoDB\Laravel\Eloquent\Model;

class Incidencia extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'incidencias';

    protected $fillable = [
        'titulo',
        'descripcion',
        'estado',
        'prioridad',
        'ubicacion',
        'usuario_id',
        'fecha_creacion',
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'ubicacion' => 'array',
    ];
}
