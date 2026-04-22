<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Obtener KPIs del gimnasio (Admin).
     *
     * Calcula tasa de ocupación, índice de cancelaciones, clientes únicos,
     * media de reservas por clase, actividad más popular, hora punta y los
     * agregados de la pantalla de métricas: reservas por día de la semana,
     * reparto asistidas/próximas/canceladas y top-N de actividades.
     *
     * Admite filtros de rango (`fecha_desde`/`fecha_hasta`) usados por el
     * dashboard, y también `mes`/`anio`/`actividad` que mantengo por
     * compatibilidad con las primeras iteraciones del endpoint.
     */
    public function kpis(Request $request): JsonResponse
    {
        $mes        = $request->query('mes');
        $anio       = $request->query('anio');
        $actividad  = $request->query('actividad');
        $fechaDesde = $request->query('fecha_desde');
        $fechaHasta = $request->query('fecha_hasta');

        // Aplico los mismos filtros a clases y reservas para que ambas
        // consultas hablen del mismo periodo
        $queryClases = Clase::query();
        $queryReservas = Reserva::query();

        if ($fechaDesde) {
            $queryClases->whereDate('fecha', '>=', $fechaDesde);
            $queryReservas->whereHas('clase', fn ($q) => $q->whereDate('fecha', '>=', $fechaDesde));
        }

        if ($fechaHasta) {
            $queryClases->whereDate('fecha', '<=', $fechaHasta);
            $queryReservas->whereHas('clase', fn ($q) => $q->whereDate('fecha', '<=', $fechaHasta));
        }

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

        // Actividad con más reservas activas dentro de los filtros aplicados
        $actividadPopular = null;
        $topActividadRow = (clone $queryReservas)
            ->where('reservas.estado', 'Activa')
            ->join('clases', 'reservas.id_clase', '=', 'clases.id_clase')
            ->join('actividades', 'clases.id_actividad', '=', 'actividades.id_actividad')
            ->select('actividades.nombre', DB::raw('COUNT(*) as total'))
            ->groupBy('actividades.nombre')
            ->orderByDesc('total')
            ->first();

        if ($topActividadRow) {
            $actividadPopular = [
                'nombre'   => $topActividadRow->nombre,
                'reservas' => (int) $topActividadRow->total,
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

        // Reservas activas agrupadas por día de la semana de la clase.
        $rowsDia = (clone $queryReservas)
            ->where('reservas.estado', 'Activa')
            ->join('clases', 'reservas.id_clase', '=', 'clases.id_clase')
            ->select(DB::raw('EXTRACT(ISODOW FROM clases.fecha)::int AS dow'), DB::raw('COUNT(*) AS total'))
            ->groupBy('dow')
            ->pluck('total', 'dow');

        $etiquetasDia = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];
        $reservasPorDiaSemana = [];
        foreach ($etiquetasDia as $iso => $etiqueta) {
            $reservasPorDiaSemana[] = [
                'dow'   => $iso,
                'label' => $etiqueta,
                'total' => (int) ($rowsDia[$iso] ?? 0),
            ];
        }

        $hoy = Carbon::today()->toDateString();

        $asistidas = (int) (clone $queryReservas)
            ->where('reservas.estado', 'Activa')
            ->whereHas('clase', fn ($q) => $q->whereDate('fecha', '<', $hoy))
            ->count();

        $proximas = (int) (clone $queryReservas)
            ->where('reservas.estado', 'Activa')
            ->whereHas('clase', fn ($q) => $q->whereDate('fecha', '>=', $hoy))
            ->count();

        $canceladasCount = $reservasCanceladas;
        $decididas       = $asistidas + $canceladasCount;

        $porcentajeAsistencia = $decididas > 0
            ? round(($asistidas / $decididas) * 100, 2)
            : 0;

        // Top 5 actividades por volumen de reservas activas
        $topActividades = (clone $queryReservas)
            ->where('reservas.estado', 'Activa')
            ->join('clases', 'reservas.id_clase', '=', 'clases.id_clase')
            ->join('actividades', 'clases.id_actividad', '=', 'actividades.id_actividad')
            ->select('actividades.nombre', DB::raw('COUNT(*) as reservas'))
            ->groupBy('actividades.nombre')
            ->orderByDesc('reservas')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'nombre'   => $row->nombre,
                'reservas' => (int) $row->reservas,
            ])
            ->values();

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
            'reservas_por_dia_semana' => $reservasPorDiaSemana,
            'asistencia' => [
                'asistidas'             => $asistidas,
                'proximas'              => $proximas,
                'canceladas'            => $canceladasCount,
                'porcentaje_asistencia' => $porcentajeAsistencia,
            ],
            'top_actividades' => $topActividades,
            'filtros_aplicados' => [
                'mes'         => $mes,
                'anio'        => $anio,
                'actividad'   => $actividad,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
            ],
        ]);
    }
}
