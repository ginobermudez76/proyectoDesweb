<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los catálogos usan borrado lógico (columna "deleted"), pero las restricciones únicas
 * originales cubrían la tabla completa sin excluir los registros ya eliminados. Esto
 * impedía reutilizar un nombre después de eliminar un tipo/subtipo, aunque el registro
 * eliminado ya no fuera relevante. Se reemplazan por índices únicos parciales que solo
 * consideran los registros activos (deleted = false).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE catalogo_tipo_incidencia DROP CONSTRAINT IF EXISTS catalogo_tipo_incidencia_nombre_unique');
        DB::statement('CREATE UNIQUE INDEX uq_catalogo_tipo_nombre_activo ON catalogo_tipo_incidencia (nombre) WHERE deleted = false');

        DB::statement('ALTER TABLE catalogo_subtipo_incidencia DROP CONSTRAINT IF EXISTS uq_subtipo_tipo_nombre');
        DB::statement('CREATE UNIQUE INDEX uq_subtipo_tipo_nombre_activo ON catalogo_subtipo_incidencia (id_tipo, nombre) WHERE deleted = false');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uq_catalogo_tipo_nombre_activo');
        DB::statement('ALTER TABLE catalogo_tipo_incidencia ADD CONSTRAINT catalogo_tipo_incidencia_nombre_unique UNIQUE (nombre)');

        DB::statement('DROP INDEX IF EXISTS uq_subtipo_tipo_nombre_activo');
        DB::statement('ALTER TABLE catalogo_subtipo_incidencia ADD CONSTRAINT uq_subtipo_tipo_nombre UNIQUE (id_tipo, nombre)');
    }
};
