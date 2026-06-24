<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opcion', function (Blueprint $table) {
            $table->string('codigo_unico')->nullable()->after('nombre_opcion');
            $table->string('ruta_enlace')->nullable()->after('codigo_unico');
            $table->string('metodo_http')->nullable()->after('ruta_enlace');
        });
    }

    public function down(): void
    {
        Schema::table('opcion', function (Blueprint $table) {
            $table->dropColumn(['codigo_unico', 'ruta_enlace', 'metodo_http']);
        });
    }
};