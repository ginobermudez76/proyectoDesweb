<?php

namespace App\Modules\Catalogos\Entities;

use Illuminate\Database\Eloquent\Model;

class CatalogoPrioridad extends Model
{
    protected $table = 'catalogo_prioridad';

    protected $fillable = [
        'uuid', 'codigo', 'label', 'orden', 'css_class', 'color_hex', 'activo', 'deleted', 'deleted_at',
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
