<?php

namespace App\Modules\Auth\Entities;

use Illuminate\Database\Eloquent\Model;

class Opcion extends Model
{
    protected $table = 'opcion';
    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre_opcion',
        'codigo_unico', // Ej: 'INCIDENCIAS_CREAR'
        'ruta_enlace',  // Ej: '/api/incidencias'
        'metodo_http',  // Ej: 'POST'
        'activo',
        'deleted'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'deleted' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Scope global para ignorar opciones eliminadas lógicamente
        static::addGlobalScope('active', function ($builder) {
            $builder->where('opcion.deleted', false);
        });
    }

    /**
     * Relación inversa con Rol.
     */
    public function roles()
    {
        return $this->belongsToMany(
            Rol::class,
            'rol_opcion',
            'id_opcion',
            'id_rol'
        )->wherePivot('deleted', false)->withTimestamps();
    }
}