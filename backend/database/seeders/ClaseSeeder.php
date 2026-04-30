<?php

namespace Database\Seeders;

use App\Models\Clase;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

class ClaseSeeder extends Seeder
{
    public function run(): void
    {
        $weekStart = CarbonImmutable::today()->startOfWeek(CarbonInterface::MONDAY);

        $weeklyClasses = [
            // Semana tipo: clases variadas para llenar historial, agenda y calendario.
            ['day' => 0, 'hora_inicio' => '09:00:00', 'hora_fin' => '10:00:00', 'cupo_maximo' => 15, 'id_sala' => 2, 'id_actividad' => 1, 'id_usuario' => 2],
            ['day' => 1, 'hora_inicio' => '18:00:00', 'hora_fin' => '19:30:00', 'cupo_maximo' => 17, 'id_sala' => 3, 'id_actividad' => 2, 'id_usuario' => 2],
            ['day' => 2, 'hora_inicio' => '19:00:00', 'hora_fin' => '20:00:00', 'cupo_maximo' => 12, 'id_sala' => 4, 'id_actividad' => 3, 'id_usuario' => 3],
            ['day' => 3, 'hora_inicio' => '18:30:00', 'hora_fin' => '19:30:00', 'cupo_maximo' => 25, 'id_sala' => 1, 'id_actividad' => 4, 'id_usuario' => 3],
            ['day' => 4, 'hora_inicio' => '20:00:00', 'hora_fin' => '21:00:00', 'cupo_maximo' => 20, 'id_sala' => 3, 'id_actividad' => 5, 'id_usuario' => 2],
            ['day' => 5, 'hora_inicio' => '10:30:00', 'hora_fin' => '11:30:00', 'cupo_maximo' => 25, 'id_sala' => 5, 'id_actividad' => 4, 'id_usuario' => 3],
        ];

        $clases = [];

        foreach ([-3, -2, -1, 1, 2, 3] as $weekOffset) {
            foreach ($weeklyClasses as $weeklyClass) {
                $clases[] = [
                    'fecha'        => $weekStart->addWeeks($weekOffset)->addDays($weeklyClass['day'])->toDateString(),
                    'hora_inicio'  => $weeklyClass['hora_inicio'],
                    'hora_fin'     => $weeklyClass['hora_fin'],
                    'cupo_maximo'  => $weeklyClass['cupo_maximo'],
                    'id_sala'      => $weeklyClass['id_sala'],
                    'id_actividad' => $weeklyClass['id_actividad'],
                    'id_usuario'   => $weeklyClass['id_usuario'],
                ];
            }
        }

        foreach ($clases as $clase) {
            Clase::create($clase);
        }
    }
}
