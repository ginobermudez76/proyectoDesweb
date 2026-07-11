<?php

namespace App\Modules\Auth\Observers;

use App\Modules\Auth\Entities\Auditoria;
use App\Modules\Auth\Entities\Usuario;

class UsuarioObserver
{
    public function updated(Usuario $usuario)
    {

        if ($usuario->wasChanged()) {
            Auditoria::create([
                'entidad' => 'usuario',
                'id_entidad' => $usuario->id,
                'accion' => 'ACTUALIZAR',
                'datos_anteriores' => json_encode($usuario->getOriginal()),
                'datos_nuevos' => json_encode($usuario->getChanges()),
                'usuario' => auth()->check() ? auth()->user()->correo_electronico : 'Sistema',
                'fecha' => now(),
            ]);
        }
    }

    public function deleted(Usuario $usuario)
    {
        Auditoria::create([
            'entidad' => 'usuario',
            'id_entidad' => $usuario->id,
            'accion' => 'ELIMINAR',
            'datos_anteriores' => json_encode($usuario->toArray()),
            'datos_nuevos' => json_encode([]),
            'usuario' => auth()->check() ? auth()->user()->correo_electronico : 'Sistema',
            'fecha' => now(),
        ]);
    }
}
