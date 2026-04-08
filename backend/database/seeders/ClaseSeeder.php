<?php

namespace Database\Seeders;

use App\Models\Clase;
use Illuminate\Database\Seeder;

class ClaseSeeder extends Seeder
{
    public function run(): void
    {
        $clases = [
            // Yoga con Laura en Sala Actividades 1
            [
                'fecha'        => '2026-03-02',
                'hora_inicio'  => '18:00:00',
                'hora_fin'     => '19:00:00',
                'cupo_maximo'  => 15,
                'id_sala'      => 2,
                'id_actividad' => 1,
                'id_usuario'   => 2,
            ],
            // Pilates con Laura en Sala Actividades 2
            [
                'fecha'        => '2026-03-03',
                'hora_inicio'  => '10:00:00',
                'hora_fin'     => '11:30:00',
                'cupo_maximo'  => 17,
                'id_sala'      => 3,
                'id_actividad' => 2,
                'id_usuario'   => 2,
            ],
            // Ciclo Indoor con Miguel en Sala Ciclo Indoor
            [
                'fecha'        => '2026-03-03',
                'hora_inicio'  => '19:00:00',
                'hora_fin'     => '20:00:00',
                'cupo_maximo'  => 12,
                'id_sala'      => 4,
                'id_actividad' => 3,
                'id_usuario'   => 3,
            ],
            // Cross-Training con Miguel en Sala Principal
            [
                'fecha'        => '2026-03-04',
                'hora_inicio'  => '19:00:00',
                'hora_fin'     => '20:00:00',
                'cupo_maximo'  => 25,
                'id_sala'      => 1,
                'id_actividad' => 4,
                'id_usuario'   => 3,
            ],
            // Zumba con Laura en Sala Actividades 2
            [
                'fecha'        => '2026-03-04',
                'hora_inicio'  => '20:00:00',
                'hora_fin'     => '21:00:00',
                'cupo_maximo'  => 20,
                'id_sala'      => 3,
                'id_actividad' => 5,
                'id_usuario'   => 2,
            ],
            // Yoga con Laura en Sala Actividades 1
            [
                'fecha'        => '2026-03-06',
                'hora_inicio'  => '10:00:00',
                'hora_fin'     => '11:00:00',
                'cupo_maximo'  => 15,
                'id_sala'      => 2,
                'id_actividad' => 1,
                'id_usuario'   => 2,
            ],
        ];

        foreach ($clases as $clase) {
            Clase::create($clase);
        }
    }
}
