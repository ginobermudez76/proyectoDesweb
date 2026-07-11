<?php

namespace App\Modules\Auth\Entities;

use Illuminate\Database\Eloquent\Model;

class Endpoint extends Model
{
    
    protected $table = 'endpoint';

    protected $fillable = [
        'uuid', 
        'nombre_endpoint', 
        'metodo', 
        'url', 
        'rbac_enabled', 
        'deleted'
    ];


    public function opciones()
    {
        
        return $this->belongsToMany(Opcion::class, 'opcion_endpoint', 'id_endpoint', 'id_opcion');
    }
}
