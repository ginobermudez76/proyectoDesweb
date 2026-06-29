<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController; 

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['rbac'])->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
});


require base_path('app/Modules/Publicaciones/Routes/api.php');