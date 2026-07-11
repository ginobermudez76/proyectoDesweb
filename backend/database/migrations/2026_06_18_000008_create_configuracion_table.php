<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid')->unique()->default(DB::raw('uuid_generate_v4()'));
            $table->string('clave', 100)->unique();
            $table->text('valor');
            $table->string('descripcion', 255)->nullable();
            
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();
            
            $table->boolean('deleted')->default(false);
            $table->timestampTz('deleted_at')->nullable();
        });

        DB::statement("ALTER TABLE configuracion ADD CONSTRAINT check_configuracion_deleted CHECK (
            (deleted = false AND deleted_at IS NULL)
            OR
            (deleted = true AND deleted_at IS NOT NULL)
        );");
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion');
    }
};
