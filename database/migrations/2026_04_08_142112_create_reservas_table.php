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
        Schema::create('reservas', function (Blueprint $table) {
            $table->id('id_reserva');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->string('estado', 50)->default('Activa');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->onDelete('cascade');
            $table->foreignId('id_clase')->constrained('clases', 'id_clase')->onDelete('cascade');
            $table->timestamps();

            // Evita que un mismo cliente reserve dos veces la misma clase
            $table->unique(['id_usuario', 'id_clase']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
