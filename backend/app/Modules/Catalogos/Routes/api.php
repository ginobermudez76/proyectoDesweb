<?php

use App\Modules\Catalogos\Controllers\CatalogosController;
use Illuminate\Support\Facades\Route;

// Catálogos: requiere autenticación + RBAC (la opción "Catálogos" se mapea a todos los roles)
Route::middleware(['throttle:api', 'rbac'])->group(function () {
    Route::get('/catalogos', [CatalogosController::class, 'index']);
});
