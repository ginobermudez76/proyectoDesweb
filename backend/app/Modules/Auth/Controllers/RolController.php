<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Entities\Rol;
use App\Modules\Auth\Entities\Opcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolController extends Controller
{
    public function index()
    {
        $roles = Rol::with('opciones')->get();
        return response()->json($roles, 200);
    }

    public function options()
    {
        $opciones = Opcion::all();
        return response()->json($opciones, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo'      => 'required|string|max:50|unique:rol,codigo',
            'nombre_rol'  => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'opciones'    => 'nullable|array',
            'opciones.*.uuid' => 'required|string|exists:opcion,uuid',
            'opciones.*.lectura' => 'required|boolean',
            'opciones.*.escritura' => 'required|boolean',
        ]);

        return DB::transaction(function () use ($validated) {
            $rol = Rol::create([
                'uuid'        => (string) Str::uuid(),
                'codigo'      => strtoupper($validated['codigo']),
                'nombre_rol'  => $validated['nombre_rol'],
                'descripcion' => $validated['descripcion'] ?? '',
                'deleted'     => false,
            ]);

            if (!empty($validated['opciones'])) {
                $uuids = array_column($validated['opciones'], 'uuid');
                $dbOpciones = Opcion::whereIn('uuid', $uuids)->get(['id', 'uuid'])->keyBy('uuid');

                foreach ($validated['opciones'] as $op) {
                    $opt = $dbOpciones->get($op['uuid']);
                    if ($opt) {
                        $rol->opciones()->attach($opt->id, [
                            'uuid'       => (string) Str::uuid(),
                            'lectura'    => $op['lectura'],
                            'escritura'  => $op['escritura'],
                            'deleted'    => false,
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            return response()->json($rol->load('opciones'), 201);
        });
    }

    public function update(Request $request, $uuid)
    {
        $rol = Rol::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'nombre_rol'  => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'opciones'    => 'nullable|array',
            'opciones.*.uuid' => 'required|string|exists:opcion,uuid',
            'opciones.*.lectura' => 'required|boolean',
            'opciones.*.escritura' => 'required|boolean',
        ]);

        return DB::transaction(function () use ($rol, $validated) {
            $rol->update([
                'nombre_rol'  => $validated['nombre_rol'],
                'descripcion' => $validated['descripcion'] ?? '',
            ]);

            // Desasociar opciones anteriores
            $rol->opciones()->detach();

            if (!empty($validated['opciones'])) {
                $uuids = array_column($validated['opciones'], 'uuid');
                $dbOpciones = Opcion::whereIn('uuid', $uuids)->get(['id', 'uuid'])->keyBy('uuid');

                foreach ($validated['opciones'] as $op) {
                    $opt = $dbOpciones->get($op['uuid']);
                    if ($opt) {
                        $rol->opciones()->attach($opt->id, [
                            'uuid'       => (string) Str::uuid(),
                            'lectura'    => $op['lectura'],
                            'escritura'  => $op['escritura'],
                            'deleted'    => false,
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            // Limpiar cache de perfiles en Redis únicamente para los usuarios que tienen asignado este rol
            $userIds = $rol->usuarios()->pluck('usuario.id');
            foreach ($userIds as $uid) {
                \Illuminate\Support\Facades\Cache::store('redis')->forget('user_profile:' . $uid);
            }

            return response()->json($rol->load('opciones'), 200);
        });
    }

    public function destroy($uuid)
    {
        $rol = Rol::where('uuid', $uuid)->firstOrFail();

        // Evitar eliminar roles del sistema críticos
        if (in_array($rol->codigo, ['ADMIN', 'SUPERVISOR', 'TECNICO', 'CIUDADANO'])) {
            return response()->json(['message' => 'No se pueden eliminar los roles del sistema.'], 403);
        }

        DB::transaction(function () use ($rol) {
            $rol->opciones()->detach();
            $rol->delete();
        });

        return response()->json(['message' => 'Rol eliminado con éxito.'], 200);
    }
}
