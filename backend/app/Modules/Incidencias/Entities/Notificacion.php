<?php

namespace App\Modules\Incidencias\Entities;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $usuario_id
 * @property string $incidencia_id
 * @property string $titulo
 * @property string $mensaje
 * @property bool $leida
 */
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
