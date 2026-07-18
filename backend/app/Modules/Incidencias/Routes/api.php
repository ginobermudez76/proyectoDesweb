<?php

use App\Modules\Incidencias\Controllers\ComentarioController;
use App\Modules\Incidencias\Controllers\EvidenciaController;
use App\Modules\Incidencias\Controllers\IncidenciaController;
use App\Modules\Incidencias\Controllers\MensajeController;
use App\Modules\Incidencias\Controllers\SeguimientoController;
use App\Modules\Incidencias\Controllers\NotificacionController;
use Illuminate\Support\Facades\Route;

// 1. Grupo general (Límite normal: 180 peticiones por minuto)
Route::middleware(['rbac', 'throttle:180,1'])->group(function () {
    // Consultas GET (Listar incidencias, ver detalles, stats)
    Route::get('dashboard/stats', [IncidenciaController::class, 'dashboardStats']);
    Route::get('incidencias', [IncidenciaController::class, 'index']);
    Route::get('incidencias/{incidencia}', [IncidenciaController::class, 'show']);

    // Actualizaciones o Eliminaciones
    Route::put('incidencias/{incidencia}', [IncidenciaController::class, 'update']);
    Route::delete('incidencias/{incidencia}', [IncidenciaController::class, 'destroy']);

    Route::post('incidencias/{id}/estado', [SeguimientoController::class, 'cambiarEstado']);
    Route::post('incidencias/{id}/comentarios', [ComentarioController::class, 'store']);
    Route::get('incidencias/{id}/mensajes', [MensajeController::class, 'index']);

    // Notificaciones
    Route::get('notificaciones', [NotificacionController::class, 'index']);
    Route::patch('notificaciones/{id}/leer', [NotificacionController::class, 'marcarLeida']);
    Route::post('notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas']);
});

// 2. Grupo ULTRA PROTEGIDO contra bots/spam (Límite: 60 peticiones por minuto)
Route::middleware(['rbac', 'throttle:60,1'])->group(function () {
    // Crear una nueva incidencia (evita que llenen la base de datos de basura)
    Route::post('incidencias', [IncidenciaController::class, 'store']);

    // Subir fotos (evita que saturen el disco duro del servidor)
    Route::post('incidencias/{id}/evidencias', [EvidenciaController::class, 'store']);

    // Enviar mensajes oficiales (evita spam de notificaciones)
    Route::post('incidencias/{id}/mensajes', [MensajeController::class, 'store']);
});
