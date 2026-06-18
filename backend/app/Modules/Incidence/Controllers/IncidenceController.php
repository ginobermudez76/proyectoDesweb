<?php

namespace App\Modules\Incidence\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class IncidenceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'El módulo de Incidencias con Arquitectura Modular está funcionando correctamente.',
            'data' => [],
        ]);
    }
}
