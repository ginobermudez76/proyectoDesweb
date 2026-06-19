<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incidencia extends Model
{
    protected $fillable = ['titulo', 'descripcion', 'estado', 'prioridad'];
}