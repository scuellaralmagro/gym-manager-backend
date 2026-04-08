<?php

namespace Database\Seeders;

use App\Models\Reserva;
use Illuminate\Database\Seeder;

class ReservaSeeder extends Seeder
{
    public function run(): void
    {
        $reservas = [
            ['estado' => 'Activa',    'id_usuario' => 4, 'id_clase' => 1], // Ana → Yoga (Lunes)
            ['estado' => 'Activa',    'id_usuario' => 5, 'id_clase' => 1], // David → Yoga (Lunes)
            ['estado' => 'Cancelada', 'id_usuario' => 6, 'id_clase' => 2], // Sara canceló Pilates (Martes)
            ['estado' => 'Activa',    'id_usuario' => 4, 'id_clase' => 3], // Ana → Ciclo (Martes)
            ['estado' => 'Activa',    'id_usuario' => 7, 'id_clase' => 3], // Jorge → Ciclo (Martes)
            ['estado' => 'Activa',    'id_usuario' => 5, 'id_clase' => 4], // David → Cross-Training (Miércoles)
            ['estado' => 'Activa',    'id_usuario' => 6, 'id_clase' => 4], // Sara → Cross-Training (Miércoles)
            ['estado' => 'Activa',    'id_usuario' => 7, 'id_clase' => 5], // Jorge → Zumba (Miércoles)
        ];

        foreach ($reservas as $reserva) {
            Reserva::create($reserva);
        }
    }
}
