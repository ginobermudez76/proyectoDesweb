<?php

use App\Modules\Auth\Controllers\UsuarioController;
use App\Modules\Auth\Controllers\RolController;
use App\Modules\Auth\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;

// Rutas públicas de invitación y 2FA challenge
Route::get('/invitacion/validar', [UsuarioController::class, 'validarInvitacion']);
Route::post('/invitacion/aceptar', [UsuarioController::class, 'aceptarInvitacion']);
Route::post('/2fa/verify', [TwoFactorController::class, 'verifyChallenge']);
Route::post('/2fa/send-email-code', [TwoFactorController::class, 'sendEmailCode']);

Route::middleware(['throttle:api', 'rbac'])->group(function () {
    Route::get('/roles', [UsuarioController::class, 'roles']);
    Route::get('/usuarios/sesiones', [UsuarioController::class, 'sesiones']);
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::get('/usuarios/tecnicos', [UsuarioController::class, 'tecnicos']);
    Route::post('/usuarios', [UsuarioController::class, 'store']);
    Route::put('/usuarios/{uuid}', [UsuarioController::class, 'update']);
    Route::patch('/usuarios/{uuid}/toggle', [UsuarioController::class, 'toggleActivo']);
    Route::post('/usuarios/{uuid}/reenviar-invitacion', [UsuarioController::class, 'reenviarInvitacion']);

    // Configuración de 2FA del Usuario Autenticado
    Route::post('/2fa/setup-app', [TwoFactorController::class, 'setupApp']);
    Route::post('/2fa/confirm-app', [TwoFactorController::class, 'confirmApp']);
    Route::post('/2fa/disable-app', [TwoFactorController::class, 'disableApp']);
    Route::post('/2fa/toggle-email', [TwoFactorController::class, 'toggleEmail']);

    // Gestión de Roles Administrativo
    Route::get('/admin/roles', [RolController::class, 'index']);
    Route::get('/admin/opciones', [RolController::class, 'options']);
    Route::post('/admin/roles', [RolController::class, 'store']);
    Route::put('/admin/roles/{uuid}', [RolController::class, 'update']);
    Route::delete('/admin/roles/{uuid}', [RolController::class, 'destroy']);
});

