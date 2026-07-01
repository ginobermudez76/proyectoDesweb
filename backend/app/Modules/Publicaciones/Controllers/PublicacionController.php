<?php

namespace App\Modules\Publicaciones\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Publicaciones\Entities\Publicacion;
use Illuminate\Http\Request;

class PublicacionController extends Controller
{
    public function index()
    {
        $publicaciones = Publicacion::where('estado', 'Publicado')
                                    ->orderBy('fecha_publicacion', 'desc')
                                    ->get();
                                    
        return response()->json($publicaciones, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:200',
            'contenido' => 'required|string',
            'resumen' => 'nullable|string|max:300',
            'estado' => 'required|in:Borrador,Publicado,Archivado',
            'etiquetas' => 'nullable|array',
        ]);

        $publicacion = new Publicacion($validated);
        
        $publicacion->autor_id = $request->user()->id;
        
        if ($validated['estado'] === 'Publicado') {
            $publicacion->fecha_publicacion = now();
        }

        $publicacion->save();

        return response()->json([
            'message' => 'Publicación creada exitosamente',
            'data' => $publicacion
        ], 201);
    }

    public function show($id)
    {
        $publicacion = Publicacion::findOrFail($id);
        return response()->json($publicacion, 200);
    }
}