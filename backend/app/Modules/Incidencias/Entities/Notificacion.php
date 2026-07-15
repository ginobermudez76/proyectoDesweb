<?php

namespace App\Modules\Incidencias\Entities;

use MongoDB\Laravel\Eloquent\Model;

class Notificacion extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'notificaciones';

    protected $fillable = [
        'usuario_id',
        'incidencia_id',
        'titulo',
        'mensaje',
        'leida',
    ];

    protected $casts = [
        'leida' => 'boolean',
        'created_at' => 'datetime',
    ];
}
