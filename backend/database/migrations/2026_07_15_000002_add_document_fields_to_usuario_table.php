<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->unsignedInteger('id_tipo_documento')->nullable();
            $table->string('documento', 50)->nullable();
            $table->string('celular', 30)->nullable();

            $table->foreign('id_tipo_documento')->references('id')->on('tipo_documento')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->dropForeign(['id_tipo_documento']);
            $table->dropColumn(['id_tipo_documento', 'documento', 'celular']);
        });
    }
};
