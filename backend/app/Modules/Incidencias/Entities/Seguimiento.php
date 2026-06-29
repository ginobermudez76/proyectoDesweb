<?php

namespace App\Modules\Incidencias\Entities;

use MongoDB\Laravel\Eloquent\Model;

class Seguimiento extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'seguimientos';

    protected $fillable = [
        'incidencia_id',
        'tecnico_id',
        'estado_anterior',
        'estado_nuevo',
        'observacion',
        'fecha_cambio'
    ];

    protected $casts = [
        'fecha_cambio' => 'datetime',
    ];
}