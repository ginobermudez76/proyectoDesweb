<?php

namespace App\Modules\Auth\Entities;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
   
    protected $connection = 'pgsql'; 
    protected $table = 'auditoria';

    
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'entidad',
        'id_entidad',
        'accion',
        'datos_anteriores',
        'datos_nuevos',
        'usuario',
        'fecha'
    ];

    
    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'fecha' => 'datetime'
    ];
}