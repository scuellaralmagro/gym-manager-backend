<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservaResource;
use App\Models\Clase;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Controlador de reservas del cliente.
 *
 * Contiene las tres acciones que puede hacer un cliente sobre sus propias
 * reservas: crear una nueva, listar las suyas y cancelar una existente.
 * La identidad del cliente se saca siempre del token Sanctum.
 */
class ReservationController extends Controller
{
    /**
     * Crear una reserva para el cliente autenticado.
     *
     * Pasos:
     *  1. Dentro de una transacción, bloqueo la fila de la clase con
     *     lockForUpdate para impedir un choque entre dos peticiones a la vez.
     *  2. Si el cliente ya tenía reserva Activa para esa clase, 409.
     *  3. Si tenía una reserva Cancelada, se reactiva en vez de crear otra fila.
     *  4. Si la clase ya ha empezado o no quedan plazas, 422.
     *
     * @param  \App\Http\Requests\StoreReservationRequest  $request  Contiene el id_clase validado.
     * @return \Illuminate\Http\JsonResponse                         Reserva creada (201), 409 si duplicada o 422 si no se puede reservar.
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        // Extraigo la identidad del token Sanctum, nunca del payload, para evitar suplantación
        $idUsuario = $request->user()->id_usuario;
        $idClase   = $request->validated('id_clase');

        // lockForUpdate bloquea la fila de la clase mientras dura la transacción.
        // Así evitamos que dos reservas concurrentes superen el cupo.
        $reserva = DB::transaction(function () use ($idUsuario, $idClase) {

            $clase = Clase::lockForUpdate()->findOrFail($idClase);

            $existente = Reserva::where('id_usuario', $idUsuario)
                ->where('id_clase', $idClase)
                ->lockForUpdate()
                ->first();

            if ($existente?->estado === 'Activa') {
                abort(response()->json([
                    'message' => 'Ya tienes una reserva activa para esta clase.',
                ], 409));
            }

            $inicioClase = Carbon::parse($clase->fecha->format('Y-m-d') . ' ' . $clase->hora_inicio);

            if ($inicioClase->isPast()) {
                abort(422, 'No se puede reservar una clase que ya ha comenzado o ha pasado.');
            }

            $ocupacionActual = Reserva::where('id_clase', $idClase)
                ->where('estado', 'Activa')
                ->count();

            if ($ocupacionActual >= $clase->cupo_maximo) {
                abort(422, 'No quedan plazas disponibles en esta clase.');
            }

            if ($existente) {
                $existente->update(['estado' => 'Activa']);
                return $existente->fresh();
            }

            return Reserva::create([
                'estado'     => 'Activa',
                'id_usuario' => $idUsuario,
                'id_clase'   => $idClase,
            ]);
        });

        return response()->json([
            'message' => 'Reserva creada correctamente.',
            'reserva' => $reserva,
        ], 201);
    }

    /**
     * Listar las reservas del cliente autenticado.
     *
     * Devuelve todas las reservas (activas y canceladas) junto con el detalle
     * de cada clase (actividad, sala, entrenador y horario). Se usa en la
     * pantalla "Mis reservas" del cliente.
     *
     * @param  \Illuminate\Http\Request  $request  Petición autenticada.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection  Colección de ReservaResource ordenada por fecha de creación desc.
     */
    public function myReservations(Request $request): AnonymousResourceCollection
    {
        $reservas = Reserva::where('id_usuario', $request->user()->id_usuario)
            ->with(['clase.actividad', 'clase.sala', 'clase.entrenador'])
            ->orderByDesc('fecha_creacion')
            ->get();

        return ReservaResource::collection($reservas);
    }

    /**
     * Cancelar una reserva propia.
     *
     * Cambia el estado a 'Cancelada'. Solo se permite cancelar reservas que
     * pertenezcan al usuario autenticado.
     *
     * @param  \Illuminate\Http\Request  $request     Petición autenticada.
     * @param  int                       $id_reserva  ID de la reserva a cancelar.
     * @return \Illuminate\Http\JsonResponse          Mensaje de éxito (200), o 409 si ya estaba cancelada.
     */
    public function cancel(Request $request, int $id_reserva): JsonResponse
    {
        $reserva = Reserva::where('id_reserva', $id_reserva)
            ->where('id_usuario', $request->user()->id_usuario)
            ->firstOrFail();

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
