<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_estado', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->default(DB::raw('uuid_generate_v4()'));
            $table->string('codigo', 50)->unique()->comment('Valor exacto usado en MongoDB: Pendiente, En Proceso, Resuelta, Rechazada');
            $table->string('label', 100)->comment('Etiqueta visible al usuario: Recibido, En proceso, Resuelto, Rechazado');
            $table->unsignedTinyInteger('orden')->default(0)->comment('Orden de presentación');
            $table->string('css_class', 100)->nullable()->comment('Clase CSS para badges: badge-recibido, badge-proceso, etc.');
            $table->string('color_hex', 10)->nullable()->comment('Color hex para uso en gráficas');
            $table->boolean('activo')->default(true);
            $table->timestampsTz();
            $table->boolean('deleted')->default(false);
            $table->timestampTz('deleted_at')->nullable();
        });

        DB::statement("
            ALTER TABLE catalogo_estado
            ADD CONSTRAINT chk_catalogo_estado_deleted
            CHECK (
                (deleted = false AND deleted_at IS NULL)
                OR (deleted = true AND deleted_at IS NOT NULL)
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_estado');
    }
};
