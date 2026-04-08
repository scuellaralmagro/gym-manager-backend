<?php

namespace Database\Seeders;

use App\Models\Sala;
use Illuminate\Database\Seeder;

class SalaSeeder extends Seeder
{
    public function run(): void
    {
        $salas = [
            ['nombre' => 'Sala Principal (Musculación)', 'capacidad_max' => 30],
            ['nombre' => 'Sala Actividades 1',           'capacidad_max' => 15],
            ['nombre' => 'Sala Actividades 2',           'capacidad_max' => 20],
            ['nombre' => 'Sala Ciclo Indoor',            'capacidad_max' => 12],
            ['nombre' => 'Espacio Exterior',             'capacidad_max' => 25],
        ];

        foreach ($salas as $sala) {
            Sala::create($sala);
        }
    }
}
