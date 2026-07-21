<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:login'])->post('/login', [AuthController::class, 'login']);
Route::post('/logs/unauthorized', [AuthController::class, 'logUnauthorizedAccess']);
Route::get('/documentos/tipos', [AuthController::class, 'tiposDocumento']);

Route::post('/register', [AuthController::class, 'register']);

Route::post('/verificar-codigo', [AuthController::class, 'verificarCodigo']); 

Route::get('/ubicaciones/paises', [AuthController::class, 'paises']);
Route::get('/ubicaciones/estados', [AuthController::class, 'estados']);
Route::get('/ubicaciones/ciudades', [AuthController::class, 'ciudades']);

Route::middleware(['throttle:api', 'rbac'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles.opciones');
    });

});

require base_path('app/Modules/Publicaciones/Routes/api.php');
require base_path('app/Modules/Incidencias/Routes/api.php');
require base_path('app/Modules/Auth/Routes/api.php');
require base_path('app/Modules/Catalogos/Routes/api.php');