<?php

namespace App\Modules\Incidencias\Entities;

use MongoDB\Laravel\Eloquent\Model;

class Mensaje extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'mensajes';

    protected $fillable = [
        'incidencia_id',
        'emisor_id',
        'emisor_nombre',
        'rol_emisor',
        'contenido',
        'leido',
        'fecha_envio',
    ];

    public $timestamps = false;
}
