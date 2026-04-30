<?php

namespace Database\Seeders;

use App\Models\Clase;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class ReservaSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = Usuario::query()
            ->whereIn('email', [
                'ana.cliente@email.com',
                'david.cliente@email.com',
                'sara.cliente@email.com',
                'jorge.cliente@email.com',
            ])
            ->pluck('id_usuario', 'email');

        $anaId = $userIds['ana.cliente@email.com'] ?? null;
        $davidId = $userIds['david.cliente@email.com'] ?? null;
        $saraId = $userIds['sara.cliente@email.com'] ?? null;
        $jorgeId = $userIds['jorge.cliente@email.com'] ?? null;

        if ($anaId === null || $davidId === null || $saraId === null || $jorgeId === null) {
            throw new \RuntimeException('ReservaSeeder no pudo resolver IDs de clientes. Ejecuta DatabaseSeeder completo o revisa UsuarioSeeder.');
        }

        $reservationPatterns = [
            ['active' => [$davidId, $saraId], 'cancelled' => [$anaId]],
            ['active' => [$davidId, $jorgeId], 'cancelled' => [$anaId]],
            ['active' => [$saraId, $jorgeId], 'cancelled' => []],
            ['active' => [$davidId, $saraId], 'cancelled' => []],
            ['active' => [$davidId, $jorgeId], 'cancelled' => [$saraId]],
            ['active' => [$saraId, $jorgeId], 'cancelled' => []],
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
            Reserva::updateOrCreate(
                [
                    'id_usuario' => $reserva['id_usuario'],
                    'id_clase' => $reserva['id_clase'],
                ],
                [
                    'estado' => $reserva['estado'],
                ],
            );
        }
    }
}
