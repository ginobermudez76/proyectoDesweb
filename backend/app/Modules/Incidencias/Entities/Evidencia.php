<?php

namespace App\Modules\Incidencias\Entities;

use MongoDB\Laravel\Eloquent\Model;

class Evidencia extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'evidencias';

    protected $fillable = [
        'incidencia_id',
        'usuario_id',
        'nombre_archivo',
        'ruta',
        'tipo_mime',
        'tamano',
        'fecha_subida',
    ];

    protected $casts = [
        'fecha_subida' => 'datetime',
    ];
}
