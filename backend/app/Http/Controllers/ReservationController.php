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

class ReservationController extends Controller
{
    /**
     * Crear una reserva.
     *
     * Reserva una plaza en una clase para el cliente autenticado.
     * Aplica control de duplicidad, validación temporal y bloqueo pesimista de aforo.
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        // Extraigo la identidad del token Sanctum, nunca del payload, para evitar suplantación
        $idUsuario = $request->user()->id_usuario;
        $idClase   = $request->validated('id_clase');

        $duplicada = Reserva::where('id_usuario', $idUsuario)
            ->where('id_clase', $idClase)
            ->where('estado', 'Activa')
            ->exists();

        if ($duplicada) {
            return response()->json([
                'message' => 'Ya tienes una reserva activa para esta clase.',
            ], 409);
        }

        // Utilizamos lockForUpdate para bloquear temporalmente (mientras se procesa la transacción)
        // la fila de la clase seleccionada, para evitar que otras transacciones concurrentes
        // superen el cupo de la clase. Otra transacción que intente leer esta fila quedará en espera.
        $reserva = DB::transaction(function () use ($idUsuario, $idClase) {

            $clase = Clase::lockForUpdate()->findOrFail($idClase);

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
     * Listar mis reservas.
     *
     * Devuelve todas las reservas del cliente autenticado con los detalles
     * de cada clase (actividad, sala, horario), ordenadas por fecha descendente.
     */
    public function myReservations(Request $request): AnonymousResourceCollection
    {
        // Obtenemos las reservas del usuario activo, con sus clases y actividades relacionadas,
        // ordenadas por fecha de creación de la reserva.
        $reservas = Reserva::where('id_usuario', $request->user()->id_usuario)
            ->with(['clase.actividad', 'clase.sala'])
            ->orderByDesc('fecha_creacion')
            ->get();

        return ReservaResource::collection($reservas);
    }

    /**
     * Cancelar una reserva propia.
     *
     * Cambia el estado de la reserva a 'Cancelada'. Solo puede cancelar
     * reservas que pertenezcan al cliente autenticado.
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
