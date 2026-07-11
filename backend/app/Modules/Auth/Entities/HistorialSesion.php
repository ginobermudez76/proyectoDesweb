<?php

namespace App\Modules\Auth\Entities;

use MongoDB\Laravel\Eloquent\Model; 

class HistorialSesion extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'historial_sesiones';

    protected $fillable = [
        'usuario_id',
        'correo_electronico',
        'accion',       
        'ip',           
        'dispositivo',  
        'fecha_hora'
    ];

    public $timestamps = false; 
}