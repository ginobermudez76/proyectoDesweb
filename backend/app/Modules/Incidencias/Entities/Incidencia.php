<?php

namespace App\Modules\Incidencias\Entities;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $titulo
 * @property string $descripcion
 * @property string $estado
 * @property string $prioridad
 * @property array $ubicacion
 * @property string $usuario_id
 * @property \Illuminate\Support\Carbon $fecha_creacion
 * @property bool $deleted
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $asignado_a
 */
class Incidencia extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'incidencias';

    protected $fillable = [
        'titulo',
        'descripcion',
        'estado',
        'prioridad',
        'ubicacion',
        'usuario_id',
        'fecha_creacion',
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'ubicacion' => 'array',
        'deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // Scope global para ignorar registros marcados como borrados lógicamente
        static::addGlobalScope('active', function ($builder) {
            $builder->where('deleted', '!=', true);
        });
    }

    /**
     * Realiza un borrado lógico actualizando las columnas deleted y deleted_at.
     */
    public function delete(): bool
    {
        $this->deleted = true;
        $this->deleted_at = now();

        return $this->save();
    }
}
