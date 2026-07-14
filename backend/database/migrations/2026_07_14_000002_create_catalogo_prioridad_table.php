<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_prioridad', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuid_generate_v4()'));
            $table->string('codigo', 50)->unique()->comment('Valor exacto: Urgente, Alta, Media, Normal, Baja');
            $table->string('label', 100)->comment('Etiqueta para mostrar al usuario');
            $table->unsignedTinyInteger('orden')->default(0)->comment('Orden de gravedad ascendente');
            $table->string('css_class', 100)->nullable()->comment('Clase CSS: badge-urgente, badge-media, badge-baja');
            $table->string('color_hex', 10)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
            $table->boolean('deleted')->default(false);
            $table->timestampTz('deleted_at')->nullable();
        });

        DB::statement("
            ALTER TABLE catalogo_prioridad
            ADD CONSTRAINT chk_catalogo_prioridad_deleted
            CHECK (
                (deleted = false AND deleted_at IS NULL)
                OR (deleted = true AND deleted_at IS NOT NULL)
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_prioridad');
    }
};
