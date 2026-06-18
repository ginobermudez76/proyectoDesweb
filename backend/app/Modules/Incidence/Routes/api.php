<?php

use App\Modules\Incidence\Controllers\IncidenceController;
use Illuminate\Support\Facades\Route;

Route::get('/incidences', [IncidenceController::class, 'index']);
