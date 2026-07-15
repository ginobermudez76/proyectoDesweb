<?php

namespace App\Modules\Auth\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Opcion extends Model
{
    protected $table = 'opcion';

    protected $primaryKey = 'id';

    protected $fillable = [
        'uuid',
        'nombre_opcion',
        'descripcion',
        'ruta',
        'deleted',
    ];

    protected $hidden = [
        'id',
    ];

    protected $casts = [
        'deleted' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('active', function ($builder) {
            $builder->where('opcion.deleted', false);
        });
    }

    public function roles()
    {
        return $this->belongsToMany(
            Rol::class,
            'rol_opcion',
            'id_opcion',
            'id_rol',
        )->wherePivot('deleted', false)->withTimestamps();
    }

    public function endpoints(): BelongsToMany
    {
        return $this->belongsToMany(
            Endpoint::class,
            'opcion_endpoint',
            'id_opcion',
            'id_endpoint',
        )->wherePivot('deleted', false)->withTimestamps();
    }
}
