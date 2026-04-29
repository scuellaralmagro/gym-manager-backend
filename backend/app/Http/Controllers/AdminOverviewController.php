<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminClaseResource;
use App\Http\Resources\AdminReservaResource;
use App\Http\Resources\UsuarioResource;
use App\Models\Clase;
use App\Models\Reserva;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Overview del panel de administración.
 *
 * Agrupa los endpoints "de consulta masiva" que necesita el admin:
 * listados paginados (reservas, clases, usuarios), el resumen ligero del
 * dashboard y la cancelación de reservas ajenas.
 */
class AdminOverviewController extends Controller
{
    /**
     * Listar reservas con paginación y filtros.
     *
     * Soporta:
     *  - 'q'        → búsqueda por nombre/apellidos/email del cliente o actividad.
     *  - 'estado'   → 'Activa' | 'Cancelada'.
     *  - 'id_clase' → filtra por una clase concreta.
     *  - 'per_page' → tamaño de página (1..50, por defecto 10).
     *
     * @param  \Illuminate\Http\Request  $request  Filtros y paginación en query string.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection  Paginador de AdminReservaResource.
     */
    public function reservas(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        $search   = trim((string) $request->query('q', ''));
        $estado   = $request->query('estado');
        $idClase  = $request->query('id_clase');

        $query = Reserva::with(['usuario', 'clase.actividad', 'clase.sala'])
            ->orderByDesc('fecha_creacion');

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($outer) use ($like) {
                $outer
                    ->whereHas('usuario', function ($q) use ($like) {
                        $q->where('nombre', 'ilike', $like)
                          ->orWhere('apellidos', 'ilike', $like)
                          ->orWhere('email', 'ilike', $like);
                    })
                    ->orWhereHas('clase.actividad', function ($q) use ($like) {
                        $q->where('nombre', 'ilike', $like);
                    });
            });
        }

        if (in_array($estado, ['Activa', 'Cancelada'], true)) {
            $query->where('estado', $estado);
        }

        if ($idClase !== null && $idClase !== '') {
            $query->where('id_clase', (int) $idClase);
        }

        $reservas = $query->paginate($perPage)->withQueryString();

        return AdminReservaResource::collection($reservas);
    }

    /**
     * Listar clases con paginación, filtros y ordenación.
     *
     * Soporta:
     *  - 'fecha_desde' / 'fecha_hasta' → rango de fechas (Y-m-d).
     *  - 'id_usuario'                   → filtra por entrenador asignado.
     *  - 'sort'                         → whitelisted: fecha, hora_inicio, cupo_maximo.
     *  - 'direction'                    → asc/desc (por defecto asc).
     *
     * Mantenemos una lista blanca de columnas ordenables para evitar
     * inyecciones a través del parámetro sort.
     *
     * @param  \Illuminate\Http\Request  $request  Filtros y paginación en query string.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection  Paginador de AdminClaseResource.
     */
    public function clases(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        $fechaDesde = $request->query('fecha_desde');
        $fechaHasta = $request->query('fecha_hasta');
        $idUsuario  = $request->query('id_usuario');

        // Whitelist de columnas ordenables para evitar inyecciones SQL
        $allowedSorts = ['fecha', 'hora_inicio', 'cupo_maximo'];
        $sort = $request->query('sort', 'fecha');
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'fecha';
        }
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc'
            ? 'desc'
            : 'asc';

        $query = Clase::with(['entrenador', 'actividad', 'sala'])
            ->withCount(['reservas as reservas_activas_count' => fn ($q) => $q->where('estado', 'Activa')]);

        if ($fechaDesde) {
            $query->whereDate('fecha', '>=', $fechaDesde);
        }

        if ($fechaHasta) {
            $query->whereDate('fecha', '<=', $fechaHasta);
        }

        if ($idUsuario !== null && $idUsuario !== '') {
            $query->where('id_usuario', (int) $idUsuario);
        }

        $query->orderBy($sort, $direction);
        if ($sort !== 'hora_inicio') {
            $query->orderBy('hora_inicio');
        }

        $clases = $query->paginate($perPage)->withQueryString();

        return AdminClaseResource::collection($clases);
    }

    /**
     * Listar usuarios con paginación y filtros.
     *
     * Soporta:
     *  - 'q'      → búsqueda por nombre, apellidos o email (ILIKE, case-insensitive).
     *  - 'id_rol' → filtra por rol (1=admin, 2=entrenador, 3=cliente).
     *  - 'per_page' → 1..50 (por defecto 10).
     *
     * El per_page se acota para evitar que un cliente malicioso pida
     * cargas enormes (p. ej. 10k filas) y tumbe la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request  Filtros y paginación en query string.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection  Paginador de UsuarioResource.
     */
    public function usuarios(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        $search = trim((string) $request->query('q', ''));
        $idRol  = $request->query('id_rol');

        $query = Usuario::with('rol')
            ->orderBy('id_rol')
            ->orderBy('nombre');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('nombre', 'ilike', $like)
                  ->orWhere('apellidos', 'ilike', $like)
                  ->orWhere('email', 'ilike', $like);
            });
        }

        if ($idRol !== null && $idRol !== '') {
            $query->where('id_rol', (int) $idRol);
        }

        $usuarios = $query->paginate($perPage)->withQueryString();

        return UsuarioResource::collection($usuarios);
    }

    /**
     * Resumen ligero para la pantalla de inicio del administrador.
     *
     * Calcula tres KPIs rápidos (reservas activas, llenado medio de la
     * semana y nuevos usuarios) más el dataset del gráfico de ocupación
     * por actividad. Se separa de /admin/informes para no arrastrar sus
     * cálculos pesados en cada login.
     *
     * Ventana temporal: semana actual (lunes 00:00 → domingo 23:59).
     *
     * @return \Illuminate\Http\JsonResponse  JSON con { kpis, ocupacion_semanal, rango }.
     */
    public function dashboardSummary(): JsonResponse
    {
        $inicioSemana = Carbon::now()->startOfWeek()->toDateString();
        $finSemana    = Carbon::now()->endOfWeek()->toDateString();

        // Reservas activas = todavía consumibles (clase de hoy en adelante)
        $hoy = Carbon::today()->toDateString();
        $reservasActivas = (int) Reserva::where('estado', 'Activa')
            ->whereHas('clase', fn ($q) => $q->whereDate('fecha', '>=', $hoy))
            ->count();

        // % llenado medio de las clases de esta semana
        $clasesSemana = Clase::whereBetween('fecha', [$inicioSemana, $finSemana])
            ->withCount(['reservas as reservas_activas_count' => fn ($q) => $q->where('estado', 'Activa')])
            ->get(['id_clase', 'id_actividad', 'cupo_maximo']);

        $cupoTotalSemana = (int) $clasesSemana->sum('cupo_maximo');
        $reservasSemana  = (int) $clasesSemana->sum('reservas_activas_count');
        $llenadoMedio    = $cupoTotalSemana > 0
            ? round(($reservasSemana / $cupoTotalSemana) * 100, 1)
            : 0;

        // Nuevos usuarios registrados esta semana
        $nuevosUsuarios = (int) Usuario::whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek(),
        ])->count();

        // Ocupación por actividad (esta semana)
        $rowsOcupacion = DB::table('clases')
            ->join('actividades', 'clases.id_actividad', '=', 'actividades.id_actividad')
            ->leftJoin('reservas', function ($join) {
                $join->on('reservas.id_clase', '=', 'clases.id_clase')
                     ->where('reservas.estado', '=', 'Activa');
            })
            ->whereBetween('clases.fecha', [$inicioSemana, $finSemana])
            ->select(
                'actividades.nombre',
                DB::raw('SUM(clases.cupo_maximo) as cupo_total'),
                DB::raw('COUNT(reservas.id_reserva) as reservas_activas'),
            )
            ->groupBy('actividades.nombre')
            ->orderBy('actividades.nombre')
            ->get();

        $ocupacionPorActividad = $rowsOcupacion->map(function ($row) {
            $cupo = (int) $row->cupo_total;
            $reservas = (int) $row->reservas_activas;
            $ocupacion = $cupo > 0 ? round(($reservas / $cupo) * 100, 1) : 0;
            return [
                'actividad' => $row->nombre,
                'ocupacion' => $ocupacion,
                'reservas'  => $reservas,
                'cupo'      => $cupo,
            ];
        })->values();

        return response()->json([
            'kpis' => [
                'reservas_activas' => $reservasActivas,
                'llenado_medio'    => $llenadoMedio,
                'nuevos_usuarios'  => $nuevosUsuarios,
            ],
            'ocupacion_semanal' => $ocupacionPorActividad,
            'rango' => [
                'desde' => $inicioSemana,
                'hasta' => $finSemana,
            ],
        ]);
    }

    /**
     * Cancelar cualquier reserva (solo Admin).
     *
     * A diferencia de ReservationController::cancel, no exige que la
     * reserva pertenezca al usuario autenticado: el administrador puede
     * cancelar la reserva de cualquier cliente (por ejemplo si avisa al
     * gimnasio de que no va a asistir).
     *
     * @param  int  $id_reserva  ID de la reserva a cancelar.
     * @return \Illuminate\Http\JsonResponse  Mensaje de éxito (200) o 409 si ya estaba cancelada.
     */
    public function cancelarReserva(int $id_reserva): JsonResponse
    {
        $reserva = Reserva::findOrFail($id_reserva);

        if ($reserva->estado === 'Cancelada') {
            return response()->json([
                'message' => 'Esta reserva ya está cancelada.',
            ], 409);
        }

        $reserva->update(['estado' => 'Cancelada']);

        return response()->json([
            'message' => 'Reserva cancelada correctamente.',
        ]);
    }
}
