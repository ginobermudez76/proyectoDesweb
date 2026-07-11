<?php

namespace App\Modules\Incidencias\Observers;

use App\Models\Auditoria;
use App\Modules\Incidencias\Entities\Incidencia;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class IncidenciaObserver
{
    public function created(Incidencia $incidencia)
    {
        $this->registrarAuditoria($incidencia, 'CREAR');
    }

    public function updated(Incidencia $incidencia)
    {
        $this->registrarAuditoria($incidencia, 'ACTUALIZAR');
    }

    public function deleted(Incidencia $incidencia)
    {
        $this->registrarAuditoria($incidencia, 'ELIMINAR');
    }

    private function registrarAuditoria(Incidencia $incidencia, $accion)
    {
        $usuario = Request::user() ? Request::user()->correo_electronico : 'Sistema';

        Auditoria::create([
            'uuid' => Str::uuid(),
            'entidad' => 'Incidencia',
            'id_entidad' => 0,
            'accion' => $accion,

            'datos_anteriores' => $accion === 'ACTUALIZAR' ? $incidencia->getOriginal() : null,

            'datos_nuevos' => $accion === 'ELIMINAR' ? null : $incidencia->getAttributes(),

            'usuario' => $usuario,
            'fecha' => now(),
        ]);
    }
}
