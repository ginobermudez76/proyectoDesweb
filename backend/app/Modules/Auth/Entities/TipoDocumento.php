<?php

namespace App\Modules\Auth\Entities;

use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    protected $table = 'tipo_documento';

    protected $fillable = [
        'codigo',
        'label',
        'validacion',
        'deleted',
    ];

    protected $casts = [
        'validacion' => 'array',
        'deleted' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('active', function ($builder) {
            $builder->where('tipo_documento.deleted', false);
        });
    }
}
