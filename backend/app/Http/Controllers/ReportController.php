<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use App\Models\Reserva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Obtener KPIs del gimnasio (Admin).
     *
     * Calcula tasa de ocupación, índice de cancelaciones, clientes únicos,
     * media de reservas por clase, actividad más popular y hora punta.
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

        $totalClases        = (int) (clone $queryClases)->count();
        $cupoTotal          = (int) $queryClases->sum('cupo_maximo');
        $reservasActivas    = (int) (clone $queryReservas)->where('estado', 'Activa')->count();
        $reservasCanceladas = (int) (clone $queryReservas)->where('estado', 'Cancelada')->count();
        $totalReservas      = $reservasActivas + $reservasCanceladas;

        $clientesUnicos = (int) (clone $queryReservas)
            ->where('estado', 'Activa')
            ->distinct('id_usuario')
            ->count('id_usuario');

        // Protejo contra divisiones por cero: si no hay clases o reservas,
        // devuelvo 0 en vez de lanzar un error
        $tasaOcupacion = $cupoTotal > 0
            ? round(($reservasActivas / $cupoTotal) * 100, 2)
            : 0;

        $indiceCancelacion = $totalReservas > 0
            ? round(($reservasCanceladas / $totalReservas) * 100, 2)
            : 0;

        $mediaReservasPorClase = $totalClases > 0
            ? round($reservasActivas / $totalClases, 2)
            : 0;

        // Subconsulta para la actividad con más reservas activas dentro de los filtros aplicados
        $actividadPopular = null;
        $topActividad = (clone $queryReservas)
            ->where('reservas.estado', 'Activa')
            ->join('clases', 'reservas.id_clase', '=', 'clases.id_clase')
            ->join('actividades', 'clases.id_actividad', '=', 'actividades.id_actividad')
            ->select('actividades.nombre', DB::raw('COUNT(*) as total'))
            ->groupBy('actividades.nombre')
            ->orderByDesc('total')
            ->first();

        if ($topActividad) {
            $actividadPopular = [
                'nombre'   => $topActividad->nombre,
                'reservas' => (int) $topActividad->total,
            ];
        }

        // Franja horaria con mayor demanda para dimensionar personal y salas
        $horaPunta = null;
        $topHora = (clone $queryReservas)
            ->where('reservas.estado', 'Activa')
            ->join('clases', 'reservas.id_clase', '=', 'clases.id_clase')
            ->select('clases.hora_inicio', DB::raw('COUNT(*) as total'))
            ->groupBy('clases.hora_inicio')
            ->orderByDesc('total')
            ->first();

        if ($topHora) {
            $horaPunta = [
                'hora_inicio' => $topHora->hora_inicio,
                'reservas'    => (int) $topHora->total,
            ];
        }

        return response()->json([
            'kpis' => [
                'tasa_ocupacion_promedio'  => $tasaOcupacion,
                'indice_cancelaciones'     => $indiceCancelacion,
                'media_reservas_por_clase' => $mediaReservasPorClase,
                'clientes_unicos'          => $clientesUnicos,
                'actividad_mas_popular'    => $actividadPopular,
                'hora_punta'               => $horaPunta,
            ],
            'desglose' => [
                'total_clases'         => $totalClases,
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
