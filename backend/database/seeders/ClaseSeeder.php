<?php

namespace Database\Seeders;

use App\Models\Clase;
use App\Models\Actividad;
use App\Models\Sala;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

class ClaseSeeder extends Seeder
{
    public function run(): void
    {
        $weekStart = CarbonImmutable::today()->startOfWeek(CarbonInterface::MONDAY);

        $roomIds = Sala::query()
            ->whereIn('nombre', [
                'Sala Principal (Musculación)',
                'Sala Actividades 1',
                'Sala Actividades 2',
                'Sala Ciclo Indoor',
                'Espacio Exterior',
            ])
            ->pluck('id_sala', 'nombre');

        $activityIds = Actividad::query()
            ->whereIn('nombre', ['Yoga', 'Pilates', 'Ciclo Indoor', 'Cross-Training', 'Zumba'])
            ->pluck('id_actividad', 'nombre');

        $trainerIds = Usuario::query()
            ->whereIn('email', ['laura.entrenadora@gymmanager.com', 'miguel.entrenador@gymmanager.com'])
            ->pluck('id_usuario', 'email');

        $weeklyClasses = [
            // Semana tipo: clases variadas para llenar historial, agenda y calendario.
            ['day' => 0, 'hora_inicio' => '09:00:00', 'hora_fin' => '10:00:00', 'cupo_maximo' => 15, 'id_sala' => $roomIds['Sala Actividades 1'] ?? null, 'id_actividad' => $activityIds['Yoga'] ?? null, 'id_usuario' => $trainerIds['laura.entrenadora@gymmanager.com'] ?? null],
            ['day' => 1, 'hora_inicio' => '18:00:00', 'hora_fin' => '19:30:00', 'cupo_maximo' => 17, 'id_sala' => $roomIds['Sala Actividades 2'] ?? null, 'id_actividad' => $activityIds['Pilates'] ?? null, 'id_usuario' => $trainerIds['laura.entrenadora@gymmanager.com'] ?? null],
            ['day' => 2, 'hora_inicio' => '19:00:00', 'hora_fin' => '20:00:00', 'cupo_maximo' => 12, 'id_sala' => $roomIds['Sala Ciclo Indoor'] ?? null, 'id_actividad' => $activityIds['Ciclo Indoor'] ?? null, 'id_usuario' => $trainerIds['miguel.entrenador@gymmanager.com'] ?? null],
            ['day' => 3, 'hora_inicio' => '18:30:00', 'hora_fin' => '19:30:00', 'cupo_maximo' => 25, 'id_sala' => $roomIds['Sala Principal (Musculación)'] ?? null, 'id_actividad' => $activityIds['Cross-Training'] ?? null, 'id_usuario' => $trainerIds['miguel.entrenador@gymmanager.com'] ?? null],
            ['day' => 4, 'hora_inicio' => '20:00:00', 'hora_fin' => '21:00:00', 'cupo_maximo' => 20, 'id_sala' => $roomIds['Sala Actividades 2'] ?? null, 'id_actividad' => $activityIds['Zumba'] ?? null, 'id_usuario' => $trainerIds['laura.entrenadora@gymmanager.com'] ?? null],
            ['day' => 5, 'hora_inicio' => '10:30:00', 'hora_fin' => '11:30:00', 'cupo_maximo' => 25, 'id_sala' => $roomIds['Espacio Exterior'] ?? null, 'id_actividad' => $activityIds['Cross-Training'] ?? null, 'id_usuario' => $trainerIds['miguel.entrenador@gymmanager.com'] ?? null],
        ];

        $clases = [];

        foreach ([-3, -2, -1, 1, 2, 3] as $weekOffset) {
            foreach ($weeklyClasses as $weeklyClass) {
                if (
                    $weeklyClass['id_sala'] === null
                    || $weeklyClass['id_actividad'] === null
                    || $weeklyClass['id_usuario'] === null
                ) {
                    throw new \RuntimeException('ClaseSeeder no pudo resolver IDs de sala/actividad/entrenador. Ejecuta DatabaseSeeder completo o revisa seeders base.');
                }

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
            Clase::firstOrCreate([
                'fecha' => $clase['fecha'],
                'hora_inicio' => $clase['hora_inicio'],
                'hora_fin' => $clase['hora_fin'],
                'id_sala' => $clase['id_sala'],
                'id_actividad' => $clase['id_actividad'],
                'id_usuario' => $clase['id_usuario'],
            ], [
                'cupo_maximo' => $clase['cupo_maximo'],
            ]);
        }
    }
}
