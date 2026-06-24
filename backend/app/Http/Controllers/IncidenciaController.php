<?php

namespace App\Http\Controllers;

// ¡Ruta corregida hacia tu módulo y modelo de MongoDB!
use App\Modules\Incidence\Entities\Incidencia;
use Illuminate\Http\Request;

class IncidenciaController extends Controller
{
    // Listar todas las incidencias
    public function index()
    {
        return response()->json(Incidencia::all(), 200);
    }

    // Guardar una nueva incidencia
    public function store(Request $request)
    {
        // 1. Validamos los datos que envía el cliente en el Body
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'prioridad' => 'required|string',
        ]);

        // 2. Agregamos los campos automáticos para MongoDB
        $validated['usuario_id'] = $request->user()->id; 
        $validated['estado'] = 'Abierta';
        $validated['fecha_creacion'] = now();

        // 3. Guardamos en la base de datos (MongoDB)
        $incidencia = Incidencia::create($validated);
        return response()->json($incidencia, 201);
    }

    // Ver una incidencia específica
    public function show($id)
    {
        return response()->json(Incidencia::findOrFail($id), 200);
    }

    // Actualizar una incidencia
    public function update(Request $request, $id)
    {
        $incidencia = Incidencia::findOrFail($id);
        $incidencia->update($request->all());
        return response()->json($incidencia, 200);
    }

    // Eliminar una incidencia
    public function destroy($id)
    {
        Incidencia::destroy($id);
        return response()->json(['message' => 'Incidencia eliminada correctamente'], 200);
    }
}