<?php

namespace App\Models;


use MongoDB\Laravel\Eloquent\Model; 

class Incidencia extends Model
{
    
    protected $connection = 'mongodb';
    protected $collection = 'incidencias';

    protected $fillable = ['titulo', 'descripcion', 'estado', 'prioridad'];
}