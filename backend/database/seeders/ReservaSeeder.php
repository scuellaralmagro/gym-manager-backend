<?php

namespace Database\Seeders;

use App\Models\Clase;
use App\Models\Reserva;
use Illuminate\Database\Seeder;

class ReservaSeeder extends Seeder
{
    public function run(): void
    {
        $reservationPatterns = [
            ['active' => [5, 6], 'cancelled' => [4]],
            ['active' => [5, 7], 'cancelled' => [4]],
            ['active' => [6, 7], 'cancelled' => []],
            ['active' => [5, 6], 'cancelled' => []],
            ['active' => [5, 7], 'cancelled' => [6]],
            ['active' => [6, 7], 'cancelled' => []],
        ];

        $reservas = [];

        $classIds = Clase::orderBy('fecha')
            ->orderBy('hora_inicio')
            ->pluck('id_clase');

        foreach ($classIds as $index => $classId) {
            $pattern = $reservationPatterns[$index % count($reservationPatterns)];

            foreach ($pattern['active'] as $userId) {
                $reservas[] = [
                    'estado'     => 'Activa',
                    'id_usuario' => $userId,
                    'id_clase'   => $classId,
                ];
            }

            foreach ($pattern['cancelled'] as $userId) {
                $reservas[] = [
                    'estado'     => 'Cancelada',
                    'id_usuario' => $userId,
                    'id_clase'   => $classId,
                ];
            }
        }

        foreach ($reservas as $reserva) {
            Reserva::create($reserva);
        }
    }
}
