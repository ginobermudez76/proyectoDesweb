<?php

namespace App\Modules\Incidencias\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Incidencias\Entities\Comentario;
use App\Modules\Incidencias\Entities\Incidencia;
use Illuminate\Http\Request;

class ComentarioController extends Controller
{
    public function store(Request $request, $id)
    {
        $validated = $request->validate([
            'texto' => 'required|string|min:3|max:1000',
        ]);

        Incidencia::findOrFail($id);

        $comentario = Comentario::create([
            'incidencia_id' => $id,
            'usuario_id' => $request->user()->id,
            'texto' => $validated['texto'],
            'fecha_creacion' => now(),
        ]);

        return response()->json([
            'message' => 'Comentario agregado correctamente',
            'comentario' => $comentario,
        ], 201);
    }
}
