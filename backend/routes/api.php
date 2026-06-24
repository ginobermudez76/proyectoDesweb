<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncidenciaController;
use App\Http\Controllers\AuthController; 


Route::post('/login', [AuthController::class, 'login']);



Route::middleware(['auth:sanctum', 'rbac'])->group(function () {
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    
    Route::apiResource('incidencias', IncidenciaController::class);
    
});