<?php

use App\Modules\Incidencias\Controllers\ComentarioController;
use App\Modules\Incidencias\Controllers\EvidenciaController;
use App\Modules\Incidencias\Controllers\IncidenciaController;
use App\Modules\Incidencias\Controllers\SeguimientoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['rbac'])->group(function () {

    Route::apiResource('incidencias', IncidenciaController::class);

    Route::post('incidencias/{id}/evidencias', [EvidenciaController::class, 'store']);

    Route::post('incidencias/{id}/estado', [SeguimientoController::class, 'cambiarEstado']);

    Route::post('incidencias/{id}/comentarios', [ComentarioController::class, 'store']);

});
