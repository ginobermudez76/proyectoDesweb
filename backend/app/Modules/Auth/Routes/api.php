<?php

use App\Modules\Auth\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:api', 'rbac'])->group(function () {
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::post('/usuarios', [UsuarioController::class, 'store']);
});
