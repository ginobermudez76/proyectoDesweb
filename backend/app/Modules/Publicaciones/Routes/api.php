<?php

use App\Modules\Publicaciones\Controllers\PublicacionController;
use Illuminate\Support\Facades\Route;

Route::get('publicaciones', [PublicacionController::class, 'index']);
Route::get('publicaciones/{id}', [PublicacionController::class, 'show']);

Route::middleware(['rbac'])->group(function () {
    Route::post('publicaciones', [PublicacionController::class, 'store']);
});
