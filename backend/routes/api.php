<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:login'])->post('/login', [AuthController::class, 'login']);

Route::middleware(['throttle:api', 'rbac'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles.opciones');
    });

});

require base_path('app/Modules/Publicaciones/Routes/api.php');
