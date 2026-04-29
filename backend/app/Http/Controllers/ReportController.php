<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador de informes (KPIs) para el rol Administrador.
 *
 * Expone un único endpoint que concentra los indicadores clave de
 * rendimiento del gimnasio. Todos los cálculos protegen contra divisiones
 * por cero devolviendo 0 o null cuando no hay datos en el periodo.
 */
class ReportController extends Controller
{
    /**
     * Calcular los KPIs del gimnasio.
     *
     * Admite filtros opcionales en query string:
     *  - 'fecha_desde' / 'fecha_hasta' → rango de fechas (Y-m-d) usado por el dashboard.
     *  - 'mes'                         → 1..12 (compatibilidad con la primera versión).
     *  - 'anio'                        → año (p. ej. 2026).
     *  - 'actividad'                   → id_actividad, para filtrar por tipo de clase.
     *
     * Devuelve:
     *  - 'kpis'                     → tasa de ocupación, cancelaciones, media, clientes únicos, actividad más popular y hora punta.
     *  - 'desglose'                 → totales brutos (clases, cupo, activas, canceladas).
     *  - 'reservas_por_dia_semana'  → serie L–D con el total de reservas activas.
     *  - 'asistencia'               → asistidas/próximas/canceladas + % asistencia.
     *  - 'top_actividades'          → top 5 de actividades por reservas activas.
     *  - 'filtros_aplicados'        → echo de los filtros recibidos.
     *
     * @param  \Illuminate\Http\Request  $request  Petición con los filtros en query string.
     * @return \Illuminate\Http\JsonResponse       JSON con todos los bloques de KPIs.
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

        // Protección contra divisiones por cero: si no hay clases o reservas,
        // devolvemos 0 en lugar de lanzar un error
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
        // ISODOW de PostgreSQL → 1=Lunes ... 7=Domingo.
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
