<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_tipo_incidencia', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuid_generate_v4()'));
            $table->string('nombre', 100)->unique();
            $table->string('icono_clase', 100)->nullable()->comment('Clase Bootstrap Icon: bi-building, bi-shield, etc.');
            $table->unsignedTinyInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
            $table->boolean('deleted')->default(false);
            $table->timestampTz('deleted_at')->nullable();
        });

        DB::statement("
            ALTER TABLE catalogo_tipo_incidencia
            ADD CONSTRAINT chk_catalogo_tipo_deleted
            CHECK (
                (deleted = false AND deleted_at IS NULL)
                OR (deleted = true AND deleted_at IS NOT NULL)
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_tipo_incidencia');
    }
};
