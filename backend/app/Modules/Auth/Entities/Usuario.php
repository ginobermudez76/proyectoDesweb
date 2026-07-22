<?php

namespace App\Modules\Auth\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $uuid
 * @property string $nombre_usuario
 * @property string $correo_electronico
 * @property string $password_hash
 * @property string $nombres
 * @property string $apellidos
 * @property bool $activo
 * @property bool $deleted
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $id_tipo_documento
 * @property string $documento
 * @property string $celular
 */
class Usuario extends Authenticatable
{
    use HasApiTokens;

    // Nombre de la tabla
    protected $table = 'usuario';

    // Llave primaria
    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    // Campos rellenables
    protected $fillable = [
        'uuid',
        'nombre_usuario',
        'correo_electronico',
        'password_hash',
        'nombres',
        'apellidos',
        'activo',
        'deleted',
        'deleted_at',
        'id_tipo_documento',
        'documento',
        'celular',
        'token_invitacion',
        'fecha_invitacion',
        'fecha_expiracion_invitacion',
        'fecha_aceptacion',
    ];

    // Ocultar atributos sensibles en serialización
    protected $hidden = [
        'id',
        'password_hash',
        'token_invitacion',
    ];

    // Casts de tipos
    protected $casts = [
        'activo' => 'boolean',
        'deleted' => 'boolean',
        'fecha_invitacion' => 'datetime',
        'fecha_expiracion_invitacion' => 'datetime',
        'fecha_aceptacion' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Sobrescribir para indicarle a Laravel que el password se almacena en 'password_hash'.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    protected static function boot()
    {
        parent::boot();

        // Scope global para ignorar registros marcados como borrados lógicamente
        static::addGlobalScope('active', function ($builder) {
            $builder->where('usuario.deleted', false);
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
     * Relación de muchos a muchos con Rol.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Rol::class,
            'rol_usuario',
            'id_usuario',
            'id_rol',
        )->wherePivot('deleted', false)->withTimestamps();
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'id_tipo_documento');
    }
}
