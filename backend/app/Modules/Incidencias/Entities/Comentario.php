<?php

namespace App\Modules\Incidencias\Entities;

use MongoDB\Laravel\Eloquent\Model;

class Comentario extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'comentarios';

    protected $fillable = [
        'incidencia_id', 
        'usuario_id',    
        'texto',
        'fecha_creacion'
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
    ];
}