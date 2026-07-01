<?php

namespace App\Modules\Auth\Entities;

use Illuminate\Database\Eloquent\Model;

class Opcion extends Model
{
    protected $table = 'opcion';
    protected $primaryKey = 'id';

    
    protected $fillable = [
        'uuid',
        'nombre_opcion',
        'descripcion',
        'deleted'
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
            'id_rol'
        )->wherePivot('deleted', false)->withTimestamps();
    }

   
    public function endpoints()
    {
        return $this->belongsToMany(
            Endpoint::class,
            'opcion_endpoint',
            'id_opcion',
            'id_endpoint'
        )->wherePivot('deleted', false)->withTimestamps();
    }
}