<?php

namespace App\Modules\Publicaciones\Entities;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @property int $autor_id
 * @property \Illuminate\Support\Carbon|null $fecha_publicacion
 * @property string $titulo
 * @property string $contenido
 * @property string|null $resumen
 * @property string $estado
 * @property array|null $etiquetas
 */
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
        'fecha_publicacion',
    ];

    protected $casts = [
        'fecha_publicacion' => 'datetime',
        'etiquetas' => 'array',
    ];
}
