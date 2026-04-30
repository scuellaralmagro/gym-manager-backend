<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Orden jerárquico estricto para respetar las dependencias de clave foránea:
     * Fase 1 → Roles, Salas, Actividades (sin FKs entre sí)
     * Fase 2 → Usuarios (depende de Roles)
     * Fase 3 → Clases (depende de Salas, Actividades, Usuarios)
     * Fase 4 → Reservas (depende de Usuarios, Clases)
     */
    public function run(): void
    {
        $this->call([
            // Fase 1: Entidades base sin dependencias cruzadas
            RolSeeder::class,
            SalaSeeder::class,
            ActividadSeeder::class,

            // Fase 2: Usuarios (requiere Roles)
            UsuarioSeeder::class,

            // Fase 3: Clases (requiere Salas, Actividades y Usuarios-Entrenadores)
            ClaseSeeder::class,

            // Fase 4: Reservas (requiere Usuarios-Clientes y Clases)
            ReservaSeeder::class,
        ]);
    }
}
