<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clases', function (Blueprint $table) {
            $table->id('id_clase');
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->integer('cupo_maximo');
            
            $table->foreignId('id_sala')->constrained('salas', 'id_sala')->onDelete('restrict');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('restrict');
            $table->foreignId('id_actividad')->constrained('actividades', 'id_actividad')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clases');
    }
};
