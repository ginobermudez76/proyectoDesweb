<?php

namespace App\Modules\Auth\Entities;

use MongoDB\Laravel\Eloquent\Model;

class IntentoLoginFallido extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'intento_login_fallidos';
    public    $timestamps = false;

    protected $fillable = [
        'correo_electronico', // Correo intentado (puede no existir en la BD)
        'ip',                 // IP del cliente
        'user_agent',         // Navegador / cliente HTTP
        'intento_numero',     // Número de intento en la secuencia actual (1-5+)
        'bloqueado',          // true si este intento generó lockout
        'fecha_hora',         // Timestamp del intento
    ];

    protected $casts = [
        'bloqueado'  => 'boolean',
        'fecha_hora' => 'datetime',
    ];
}
