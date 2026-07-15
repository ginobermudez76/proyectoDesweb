<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opcion', function (Blueprint $table) {
            $table->string('ruta', 255)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('opcion', function (Blueprint $table) {
            $table->dropColumn(['ruta']);
        });
    }
};
