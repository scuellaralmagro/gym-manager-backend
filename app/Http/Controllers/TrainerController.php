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

/**
 * Controlador específico del rol Entrenador.
 *
 * Solo lo usan los usuarios con rol 'Entrenador': consultar su propia
 * agenda, ver sus especialidades y revisar el listado de clientes
 * reservados en cada una de sus clases.
 */
class TrainerController extends Controller
{
    /**
     * Agenda del entrenador autenticado.
     *
     * Devuelve las clases que imparte, con sala, actividad y plazas
     * ocupadas. Acepta los parámetros opcionales 'desde' y 'hasta'
     * para la vista calendario por semanas. Si no se envía
     * rango, devuelve solo las clases de hoy en adelante (modo dashboard).
     *
     * @param  \Illuminate\Http\Request  $request  Puede incluir 'desde' y 'hasta' en query string.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection  Colección de ClaseResource.
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
     * Especialidades del entrenador autenticado.
     *
     * Devuelve los nombres únicos de las actividades que el entrenador
     * tiene asignadas en alguna clase (pasadas o futuras). Se muestra en
     * la pantalla "Mi perfil" del entrenador como campo de solo lectura.
     *
     * @param  \Illuminate\Http\Request  $request  Petición autenticada.
     * @return \Illuminate\Http\JsonResponse       JSON { data: string[] } con los nombres de las actividades.
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
     * Listar la asistencia (clientes reservados) de una clase.
     *
     * Solo el entrenador asignado a la clase puede consultarla; si otro
     * entrenador intenta ver una clase que no es suya, devolvemos 403. Si
     * la clase no existe, 404.
     *
     * @param  \Illuminate\Http\Request  $request   Petición autenticada.
     * @param  int                       $id_clase  ID de la clase.
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *         Colección de AsistenciaResource, o JSON con mensaje si no hay asistentes / 404 / 403.
     */
    public function attendance(Request $request, int $id_clase): JsonResponse|AnonymousResourceCollection
    {
        $clase = Clase::find($id_clase);

        if (! $clase) {
            return response()->json(['message' => 'Clase no encontrada.'], 404);
        }

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
