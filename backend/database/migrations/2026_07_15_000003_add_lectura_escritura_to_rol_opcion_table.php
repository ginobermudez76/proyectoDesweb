<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rol_opcion', function (Blueprint $table) {
            $table->boolean('lectura')->default(true);
            $table->boolean('escritura')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('rol_opcion', function (Blueprint $table) {
            $table->dropColumn(['lectura', 'escritura']);
        });
    }
};
