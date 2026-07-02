<?php

namespace App\Modules\Publicaciones\Entities;

use MongoDB\Laravel\Eloquent\Model;

class Publicacion extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'publicaciones';

    protected $fillable = [
        'titulo',
        'contenido',
        'resumen',
        'autor_id', 
        'estado',   
        'etiquetas', 
        'fecha_publicacion'
    ];

    protected $casts = [
        'fecha_publicacion' => 'datetime',
        'etiquetas' => 'array', 
    ];
}