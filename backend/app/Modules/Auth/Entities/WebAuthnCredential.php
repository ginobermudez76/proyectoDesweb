<?php

namespace App\Modules\Auth\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $id_usuario
 * @property string $credential_id
 * @property string $public_key
 * @property int $counter
 * @property string $name
 * @property bool $deleted
 */
class WebAuthnCredential extends Model
{
    protected $table = 'webauthn_credentials';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'uuid',
        'id_usuario',
        'credential_id',
        'public_key',
        'counter',
        'name',
        'deleted',
    ];

    protected $hidden = [
        'id',
        'public_key',
    ];

    protected $casts = [
        'counter' => 'integer',
        'deleted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('active', function ($builder) {
            $builder->where('webauthn_credentials.deleted', false);
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
