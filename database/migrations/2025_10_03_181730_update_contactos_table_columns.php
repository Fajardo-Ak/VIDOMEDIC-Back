<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contactos', function (Blueprint $table) {
            // Eliminar parentesco
            $table->dropColumn('parentesco');
            
            // Agregar correo (opcional)
            $table->string('correo')->nullable()->after('telefono');
        });
    }

    public function down()
    {
        Schema::table('contactos', function (Blueprint $table) {
            // Revertir cambios
            $table->string('parentesco');
            $table->dropColumn('correo');
        });
    }
};