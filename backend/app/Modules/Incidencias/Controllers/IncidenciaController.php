<?php

namespace App\Modules\Incidencias\Controllers;

use App\Http\Controllers\Controller; 
use App\Modules\Incidencias\Entities\Incidencia; 
use App\Modules\Incidencias\Entities\Evidencia;
use App\Modules\Incidencias\Entities\Seguimiento;
use App\Modules\Incidencias\Entities\Comentario;
use Illuminate\Http\Request;

class IncidenciaController extends Controller
{
    public function index()
    {
        return response()->json(Incidencia::all(), 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'prioridad' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $incidencia = new Incidencia();
        $incidencia->titulo = $validated['titulo'];
        $incidencia->descripcion = $validated['descripcion'];
        $incidencia->prioridad = $validated['prioridad'];
        $incidencia->estado = 'Pendiente';
        $incidencia->usuario_id = $request->user()->id; 
        $incidencia->fecha_creacion = now();
        
        $incidencia->ubicacion = [
            'type' => 'Point',
            'coordinates' => [(float)$validated['lng'], (float)$validated['lat']]
        ];

        $incidencia->save();

        return response()->json($incidencia, 201);
    }

    public function show($id)
    {
        $incidencia = Incidencia::findOrFail($id);
        $evidencias = Evidencia::where('incidencia_id', $id)->get();
        $historial_seguimiento = Seguimiento::where('incidencia_id', $id)
                                            ->orderBy('fecha_cambio', 'desc')
                                            ->get();
        $comentarios = Comentario::where('incidencia_id', $id)
                                  ->orderBy('fecha_creacion', 'asc')
                                  ->get();

        return response()->json([
            'incidencia' => $incidencia,
            'evidencias' => $evidencias,
            'historial_seguimiento' => $historial_seguimiento,
            'comentarios' => $comentarios
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $incidencia = Incidencia::findOrFail($id);
        $incidencia->update($request->all());
        return response()->json($incidencia, 200);
    }

    public function destroy($id)
    {
        Incidencia::destroy($id);
        return response()->json(['message' => 'Incidencia eliminada correctamente'], 200);
    }
}