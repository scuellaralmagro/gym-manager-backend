<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use App\Models\Reserva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Obtener KPIs del gimnasio (Admin).
     *
     * Calcula tasa de ocupación promedio e índice de cancelaciones.
     * Admite filtros opcionales por mes, año y actividad via query string.
     *
     * @queryParam mes int Filtrar por mes (1-12). Example: 4
     * @queryParam anio int Filtrar por año. Example: 2026
     * @queryParam actividad int Filtrar por id_actividad. Example: 1
     */
    public function kpis(Request $request): JsonResponse
    {
        $mes       = $request->query('mes');
        $anio      = $request->query('anio');
        $actividad = $request->query('actividad');

        // Aplico los mismos filtros temporales a clases y reservas para que
        // los KPIs reflejen el mismo rango de datos en ambas consultas
        $queryClases = Clase::query();
        $queryReservas = Reserva::query();

        if ($mes) {
            $queryClases->whereMonth('fecha', $mes);
            $queryReservas->whereHas('clase', fn ($q) => $q->whereMonth('fecha', $mes));
        }

        if ($anio) {
            $queryClases->whereYear('fecha', $anio);
            $queryReservas->whereHas('clase', fn ($q) => $q->whereYear('fecha', $anio));
        }

        if ($actividad) {
            $queryClases->where('id_actividad', $actividad);
            $queryReservas->whereHas('clase', fn ($q) => $q->where('id_actividad', $actividad));
        }

        $cupoTotal      = (int) $queryClases->sum('cupo_maximo');
        $reservasActivas = (int) (clone $queryReservas)->where('estado', 'Activa')->count();
        $reservasCanceladas = (int) (clone $queryReservas)->where('estado', 'Cancelada')->count();
        $totalReservas   = $reservasActivas + $reservasCanceladas;

        // Protejo contra divisiones por cero: si no hay clases o reservas,
        // devuelvo 0 en vez de lanzar un error
        $tasaOcupacion     = $cupoTotal > 0
            ? round(($reservasActivas / $cupoTotal) * 100, 2)
            : 0;

        $indiceCancelacion = $totalReservas > 0
            ? round(($reservasCanceladas / $totalReservas) * 100, 2)
            : 0;

        return response()->json([
            'kpis' => [
                'tasa_ocupacion_promedio' => $tasaOcupacion,
                'indice_cancelaciones'    => $indiceCancelacion,
            ],
            'desglose' => [
                'cupo_total'           => $cupoTotal,
                'reservas_activas'     => $reservasActivas,
                'reservas_canceladas'  => $reservasCanceladas,
                'total_reservas'       => $totalReservas,
            ],
            'filtros_aplicados' => [
                'mes'       => $mes,
                'anio'      => $anio,
                'actividad' => $actividad,
            ],
        ]);
    }
}
