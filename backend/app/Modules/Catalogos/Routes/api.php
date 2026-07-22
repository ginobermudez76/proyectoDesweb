<?php

use App\Modules\Catalogos\Controllers\CatalogosController;
use Illuminate\Support\Facades\Route;

// Catálogos: requiere autenticación + RBAC (la opción "Catálogos" se mapea a todos los roles)
Route::middleware(['throttle:api', 'rbac'])->group(function () {
    Route::get('/catalogos', [CatalogosController::class, 'index']);

    // Escritura: solo quien tenga permiso de escritura en "Catálogos" (hoy, únicamente Admin).
    Route::put('/catalogos/estados/{uuid}', [CatalogosController::class, 'updateEstado']);
    Route::put('/catalogos/prioridades/{uuid}', [CatalogosController::class, 'updatePrioridad']);

    Route::post('/catalogos/tipos', [CatalogosController::class, 'storeTipo']);
    Route::put('/catalogos/tipos/{uuid}', [CatalogosController::class, 'updateTipo']);
    Route::delete('/catalogos/tipos/{uuid}', [CatalogosController::class, 'destroyTipo']);

    Route::post('/catalogos/subtipos', [CatalogosController::class, 'storeSubtipo']);
    Route::put('/catalogos/subtipos/{uuid}', [CatalogosController::class, 'updateSubtipo']);
    Route::delete('/catalogos/subtipos/{uuid}', [CatalogosController::class, 'destroySubtipo']);
});
