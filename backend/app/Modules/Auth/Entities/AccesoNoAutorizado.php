<?php

namespace App\Modules\Auth\Entities;

use MongoDB\Laravel\Eloquent\Model;

class AccesoNoAutorizado extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'accesos_no_autorizados';
    public    $timestamps = false;

    protected $fillable = [
        'usuario_uuid',       // UUID del usuario autenticado que hizo la petición
        'correo_electronico', // Correo del usuario autenticado
        'rol',                // Rol del usuario en el momento del intento
        'ip',                 // IP del cliente
        'user_agent',         // Navegador / cliente HTTP
        'metodo',             // Método HTTP (GET, POST, PUT, DELETE…)
        'url',                // Ruta intentada
        'tipo_violacion',     // 'RBAC' | 'IDOR'
        'detalle',            // Descripción del intento
        'fecha_hora',         // Timestamp del evento
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];
}
