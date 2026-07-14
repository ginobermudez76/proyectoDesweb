<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_subtipo_incidencia', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuid_generate_v4()'));
            $table->unsignedBigInteger('id_tipo')->comment('FK a catalogo_tipo_incidencia');
            $table->string('nombre', 100);
            $table->unsignedTinyInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
            $table->boolean('deleted')->default(false);
            $table->timestampTz('deleted_at')->nullable();

            $table->foreign('id_tipo')
                  ->references('id')
                  ->on('catalogo_tipo_incidencia')
                  ->onDelete('restrict');

            $table->unique(['id_tipo', 'nombre'], 'uq_subtipo_tipo_nombre');
        });

        DB::statement("
            ALTER TABLE catalogo_subtipo_incidencia
            ADD CONSTRAINT chk_catalogo_subtipo_deleted
            CHECK (
                (deleted = false AND deleted_at IS NULL)
                OR (deleted = true AND deleted_at IS NOT NULL)
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_subtipo_incidencia');
    }
};
