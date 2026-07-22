<?php

namespace App\Modules\Auth\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $codigo
 * @property string $nombre_rol
 * @property string|null $descripcion
 * @property bool $deleted
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Rol extends Model
{
    // Nombre de la tabla
    protected $table = 'rol';

    // Llave primaria
    protected $primaryKey = 'id';

    // Desactivar incremento si no fuera autoincremental (pero sí lo es)
    public $incrementing = true;

    // Tipo de la llave primaria
    protected $keyType = 'int';

    // Campos rellenables
    protected $fillable = [
        'uuid',
        'codigo',
        'nombre_rol',
        'descripcion',
        'req_token_invitacion',
        'deleted',
        'deleted_at',
    ];

    // Ocultar atributos en serialización
    protected $hidden = [
        'id',
    ];

    // Casts de tipos
    protected $casts = [
        'req_token_invitacion' => 'boolean',
        'deleted' => 'boolean',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // Scope global para ignorar registros marcados como borrados lógicamente
        static::addGlobalScope('active', function ($builder) {
            $builder->where('rol.deleted', false);
        });

        // Generar UUID al crear si no está provisto
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
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

    /**
     * Relación de muchos a muchos con Usuario.
     */
    public function usuarios()
    {
        return $this->belongsToMany(
            Usuario::class,
            'rol_usuario',
            'id_rol',
            'id_usuario',
        )->wherePivot('deleted', false)->withTimestamps();
    }

    public function opciones(): BelongsToMany
    {
        return $this->belongsToMany(
            Opcion::class,
            'rol_opcion',
            'id_rol',
            'id_opcion',
        )->withPivot('lectura', 'escritura', 'deleted')->withTimestamps();
    }
}
