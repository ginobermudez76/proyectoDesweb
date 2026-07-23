<?php

namespace App\Modules\Auth\Observers;

use App\Modules\Auth\Entities\Auditoria;
use Illuminate\Database\Eloquent\Model;

/**
 * Observer genérico de auditoría reutilizable por cualquier módulo: registra CREAR/ACTUALIZAR/ELIMINAR
 * en la tabla `auditoria` sin necesidad de una clase de observer dedicada por entidad.
 *
 * Laravel::observe() solo recuerda el nombre de la clase del observer (incluso si se le pasa una
 * instancia ya construida) y la vuelve a resolver desde el contenedor cada vez que dispara un evento,
 * descartando cualquier argumento de constructor. Por eso el nombre de la entidad no puede recibirse
 * por constructor: se deriva del propio modelo (getTable() funciona igual para Eloquent y para los
 * modelos de Mongo de mongodb/laravel-mongodb).
 */
class EntidadAuditObserver
{
    public function created(Model $model): void
    {
        $this->registrar($model, 'CREAR', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        if (!$model->wasChanged()) {
            return;
        }

        // Los catálogos usan soft-delete (columna `deleted`) en vez de Model::delete().
        $accion = $model->wasChanged('deleted') && $model->deleted ? 'ELIMINAR' : 'ACTUALIZAR';

        $this->registrar($model, $accion, $model->getOriginal(), $accion === 'ELIMINAR' ? null : $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->registrar($model, 'ELIMINAR', $model->getAttributes(), null);
    }

    private function registrar(Model $model, string $accion, ?array $antes, ?array $despues): void
    {
        Auditoria::create([
            'entidad' => $model->getTable(),
            'id_entidad' => is_int($model->id) ? $model->id : 0,
            'accion' => $accion,
            'datos_anteriores' => $antes,
            'datos_nuevos' => $despues,
            'usuario' => auth()->check() ? auth()->user()->correo_electronico : 'Sistema',
            'fecha' => now(),
        ]);
    }
}
