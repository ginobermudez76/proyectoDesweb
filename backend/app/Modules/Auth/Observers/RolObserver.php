<?php

namespace App\Modules\Auth\Observers;

use App\Modules\Auth\Entities\Auditoria;
use App\Modules\Auth\Entities\Rol;

class RolObserver
{
    public function updated(Rol $rol)
    {
        if ($rol->wasChanged()) {
            Auditoria::create([
                'entidad' => 'rol',
                'id_entidad' => $rol->id,
                'accion' => 'ACTUALIZAR',
                'datos_anteriores' => json_encode($rol->getOriginal()),
                'datos_nuevos' => json_encode($rol->getChanges()),
                'usuario' => auth()->check() ? auth()->user()->correo_electronico : 'Sistema',
                'fecha' => now(),
            ]);
        }
    }

    public function deleted(Rol $rol)
    {
        Auditoria::create([
            'entidad' => 'rol',
            'id_entidad' => $rol->id,
            'accion' => 'ELIMINAR',
            'datos_anteriores' => json_encode($rol->toArray()),
            'datos_nuevos' => json_encode([]),
            'usuario' => auth()->check() ? auth()->user()->correo_electronico : 'Sistema',
            'fecha' => now(),
        ]);
    }
}
