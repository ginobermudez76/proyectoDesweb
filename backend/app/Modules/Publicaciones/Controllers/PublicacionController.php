<?php

namespace App\Modules\Publicaciones\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Entities\Usuario;
use App\Modules\Publicaciones\Entities\Publicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PublicacionController extends Controller
{
    public function index()
    {
        $publicaciones = Publicacion::where('estado', 'Publicado')
            ->orderBy('fecha_publicacion', 'desc')
            ->get();

        return response()->json($this->conNombreAutor($publicaciones), 200);
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

        /** @var Publicacion $publicacion */
        $publicacion = new Publicacion($validated);

        $publicacion->autor_id = $request->user()->id;

        if ($validated['estado'] === 'Publicado') {
            $publicacion->fecha_publicacion = now();
        }

        $publicacion->save();

        return response()->json([
            'message' => 'Publicación creada exitosamente',
            'data' => $publicacion,
        ], 201);
    }

    public function show($id)
    {
        $publicacion = Publicacion::findOrFail($id);

        return response()->json($this->conNombreAutor(collect([$publicacion]))->first(), 200);
    }

    public function update(Request $request, $id)
    {
        $publicacion = Publicacion::findOrFail($id);

        $validated = $request->validate([
            'titulo' => 'required|string|max:200',
            'contenido' => 'required|string',
            'resumen' => 'nullable|string|max:300',
            'estado' => 'required|in:Borrador,Publicado,Archivado',
            'etiquetas' => 'nullable|array',
        ]);

        if ($validated['estado'] === 'Publicado' && !$publicacion->fecha_publicacion) {
            $validated['fecha_publicacion'] = now();
        }

        $publicacion->fill($validated);
        $publicacion->save();

        return response()->json([
            'message' => 'Publicación actualizada exitosamente',
            'data' => $this->conNombreAutor(collect([$publicacion]))->first(),
        ], 200);
    }

    public function destroy($id)
    {
        $publicacion = Publicacion::findOrFail($id);
        $publicacion->delete();

        return response()->json(['message' => 'Publicación eliminada exitosamente'], 200);
    }

    /**
     * Adjunta el nombre completo del autor (usuario en Postgres) a cada publicación (Mongo).
     */
    private function conNombreAutor(Collection $publicaciones): Collection
    {
        $autorIds = $publicaciones->pluck('autor_id')->filter()->unique()->values();

        $nombres = Usuario::whereIn('id', $autorIds)
            ->get(['id', 'nombres', 'apellidos'])
            ->keyBy('id');

        return $publicaciones->map(function (Publicacion $publicacion) use ($nombres) {
            $autor = $nombres->get($publicacion->autor_id);
            $publicacion->autor_nombre = $autor ? trim("{$autor->nombres} {$autor->apellidos}") : null;

            return $publicacion;
        });
    }
}
