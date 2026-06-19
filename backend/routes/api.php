<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncidenciaController;
use App\Http\Controllers\AuthController; 

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API para incidencias
Route::apiResource('incidencias', IncidenciaController::class);
Route::post('/login', [AuthController::class, 'login']);