<?php

namespace App\Http\Controllers;

use App\Http\Resources\AsistenciaResource;
use App\Http\Resources\ClaseResource;
use App\Models\Actividad;
use App\Models\Clase;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TrainerController extends Controller
{
    /**
     * Agenda del entrenador.
     *
     * Devuelve las clases asignadas al entrenador autenticado con sala,
     * actividad y plazas disponibles. Acepta los parámetros opcionales
     * `desde` y `hasta` (formato Y-m-d) para poder consultar semanas pasadas
     * desde la vista calendario. Si no se envía rango, por compatibilidad
     * devuelve solo las clases de hoy en adelante.
     */
    public function agenda(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);

        $query = Clase::where('id_usuario', $request->user()->id_usuario)
            ->with(['sala', 'actividad'])
            ->withCount(['reservas as reservas_activas_count' => fn ($q) => $q->where('estado', 'Activa')])
            ->orderBy('fecha')
            ->orderBy('hora_inicio');

        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        if ($desde || $hasta) {
            if ($desde) {
                $query->where('fecha', '>=', $desde);
            }
            if ($hasta) {
                $query->where('fecha', '<=', $hasta);
            }
        } else {
            $query->where('fecha', '>=', Carbon::today());
        }

        return ClaseResource::collection($query->get());
    }

    /**
     * Especialidades del entrenador.
     *
     * Devuelve los nombres únicos de las actividades que el entrenador
     * autenticado tiene asignadas en alguna clase (pasadas o futuras).
     * Se usa en la pantalla "Mi perfil" a modo informativo, como campo
     * de solo lectura. Si no tiene ninguna clase asignada devuelve un
     * array vacío en 'data'.
     */
    public function especialidades(Request $request): JsonResponse
    {
        $especialidades = Actividad::whereIn(
            'id_actividad',
            Clase::where('id_usuario', $request->user()->id_usuario)
                ->select('id_actividad')
        )
            ->orderBy('nombre')
            ->pluck('nombre')
            ->all();

        return response()->json(['data' => $especialidades]);
    }

    /**
     * Consultar asistencia de una clase.
     *
     * Lista los clientes con reserva activa en la clase indicada.
     * Solo el entrenador asignado puede consultar esta información (403 si no coincide).
     */
    public function attendance(Request $request, int $id_clase): JsonResponse|AnonymousResourceCollection
    {
        $clase = Clase::find($id_clase);

        if (! $clase) {
            return response()->json(['message' => 'Clase no encontrada.'], 404);
        }

        // Solo el entrenador asignado puede consultar la asistencia de su propia clase
        if ($clase->id_usuario !== $request->user()->id_usuario) {
            abort(403, 'No tienes permiso para ver la asistencia de esta clase.');
        }

        $reservas = Reserva::where('id_clase', $id_clase)
            ->where('estado', 'Activa')
            ->with('usuario')
            ->orderBy('fecha_creacion')
            ->get();

        if ($reservas->isEmpty()) {
            return response()->json([
                'message' => '0 reservas activas.',
                'data'    => [],
            ]);
        }

        return AsistenciaResource::collection($reservas);
    }
}
