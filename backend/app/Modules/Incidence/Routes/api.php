<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Incidence\Controllers\IncidenceController;

Route::get('/incidences', [IncidenceController::class, 'index']);
