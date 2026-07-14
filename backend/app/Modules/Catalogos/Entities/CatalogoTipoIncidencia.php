<?php

namespace App\Modules\Catalogos\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogoTipoIncidencia extends Model
{
    protected $table = 'catalogo_tipo_incidencia';

    protected $fillable = [
        'uuid', 'nombre', 'icono_clase', 'orden', 'activo', 'deleted', 'deleted_at',
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

    public function subtipos(): HasMany
    {
        return $this->hasMany(CatalogoSubtipoIncidencia::class, 'id_tipo')
                    ->where('deleted', false)
                    ->where('activo', true)
                    ->orderBy('orden');
    }
}
