<?php

namespace App\Modules\Catalogos\Entities;

use Illuminate\Database\Eloquent\Model;

class CatalogoSubtipoIncidencia extends Model
{
    protected $table = 'catalogo_subtipo_incidencia';

    protected $fillable = [
        'uuid', 'id_tipo', 'nombre', 'orden', 'activo', 'deleted', 'deleted_at',
    ];

    protected $casts = [
        'activo'  => 'boolean',
        'deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope('active', fn ($q) => $q->where('deleted', false)->where('activo', true));
    }
}
