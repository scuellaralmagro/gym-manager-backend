<?php

namespace Database\Seeders;

use App\Models\Actividad;
use Illuminate\Database\Seeder;

class ActividadSeeder extends Seeder
{
    public function run(): void
    {
        $actividades = [
            ['nombre' => 'Yoga',           'descripcion' => 'Clase de hatha yoga para todos los niveles.'],
            ['nombre' => 'Pilates',        'descripcion' => 'Tipo de gimnasia que se centra en movilidad y fuerza.'],
            ['nombre' => 'Ciclo Indoor',   'descripcion' => 'Entrenamiento tipo cardio realizado en bicicleta estática.'],
            ['nombre' => 'Cross-Training', 'descripcion' => 'Circuito de fuerza y resistencia con pesas y peso corporal.'],
            ['nombre' => 'Zumba',          'descripcion' => 'Clase aeróbica con ritmos latinos orientada a todos los públicos.'],
        ];

        foreach ($actividades as $actividad) {
            Actividad::create($actividad);
        }
    }
}
