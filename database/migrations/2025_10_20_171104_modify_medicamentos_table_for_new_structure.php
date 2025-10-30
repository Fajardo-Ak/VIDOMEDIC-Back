<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Crear columna temporal para backup de frecuencia
        Schema::table('medicamentos', function (Blueprint $table) {
            $table->string('frecuencia_old')->nullable()->after('frecuencia');
        });
        
        // Copiar datos antiguos a la columna temporal
        DB::statement('UPDATE medicamentos SET frecuencia_old = frecuencia');
        
        // Eliminar columnas que ya no van
        Schema::table('medicamentos', function (Blueprint $table) {
            $table->dropColumn(['frecuencia', 'importancia']);
        });
        
        // Renombrar 'dosis' a 'presentacion'
        Schema::table('medicamentos', function (Blueprint $table) {
            $table->renameColumn('dosis', 'presentacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('medicamentos', function (Blueprint $table) {
            $table->renameColumn('presentacion', 'dosis');
            $table->string('frecuencia')->nullable();
            $table->enum('importancia', ['Alta', 'Media', 'Baja'])->default('Baja');
            $table->dropColumn('frecuencia_old');
        });
    }
};
