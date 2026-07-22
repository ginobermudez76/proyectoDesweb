<?php

use App\Modules\Publicaciones\Controllers\PublicacionController;
use Illuminate\Support\Facades\Route;

$publicacionId = 'publicaciones/{id}';

Route::get('publicaciones', [PublicacionController::class, 'index']);
Route::get($publicacionId, [PublicacionController::class, 'show']);

Route::middleware(['rbac'])->group(function () use ($publicacionId) {
    Route::post('publicaciones', [PublicacionController::class, 'store']);
    Route::put($publicacionId, [PublicacionController::class, 'update']);
    Route::delete($publicacionId, [PublicacionController::class, 'destroy']);
});
