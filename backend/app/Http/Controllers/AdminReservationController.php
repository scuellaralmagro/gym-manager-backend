<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminReservationRequest;
use App\Http\Resources\AdminReservaResource;
use App\Models\Reserva;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Creación forzada de reservas desde el panel de administración.
 *
 * A diferencia de ReservationController (que solo deja al cliente
 * reservarse a sí mismo), este endpoint permite al administrador dar de
 * alta una reserva para cualquier cliente en cualquier clase (con las
 * validaciones mínimas de integridad que aplica StoreAdminReservationRequest).
 */
class AdminReservationController extends Controller
{
    /**
     * Crear (o reactivar) una reserva desde el panel de administración.
     *
     * Casos contemplados:
     *  - No existe reserva (usuario, clase) previa → se crea nueva en Activa.
     *  - Existe y está en 'Cancelada' → se reactiva como 'Activa'.
     *  - Existe y está en 'Activa' → devolvemos 409 (no tiene sentido duplicar).
     *
     * Todo dentro de una transacción con lockForUpdate para evitar
     * condiciones de carrera si llegan dos peticiones simultáneas sobre la
     * misma pareja (usuario, clase).
     *
     * @param  \App\Http\Requests\StoreAdminReservationRequest  $request  id_usuario e id_clase validados.
     * @return \Illuminate\Http\JsonResponse                              Reserva creada/reactivada (201) o 409 si ya estaba activa.
     */
    public function store(StoreAdminReservationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $idUsuario = (int) $data['id_usuario'];
        $idClase   = (int) $data['id_clase'];

        $reserva = DB::transaction(function () use ($idUsuario, $idClase) {
            // lockForUpdate bloquea la fila si existía, evitando problemas
            // con cancelaciones concurrentes sobre la misma (cliente, clase).
            $existente = Reserva::where('id_usuario', $idUsuario)
                ->where('id_clase', $idClase)
                ->lockForUpdate()
                ->first();

            if ($existente) {
                if ($existente->estado === 'Activa') {
                    abort(response()->json([
                        'message' => 'El cliente ya tiene una reserva activa para esta clase.',
                    ], 409));
                }

                $existente->update(['estado' => 'Activa']);
                return $existente->fresh();
            }

            return Reserva::create([
                'estado'     => 'Activa',
                'id_usuario' => $idUsuario,
                'id_clase'   => $idClase,
            ]);
        });

        $reserva->load(['usuario', 'clase.actividad', 'clase.sala']);

        return (new AdminReservaResource($reserva))
            ->additional(['message' => 'Reserva forzada correctamente.'])
            ->response()
            ->setStatusCode(201);
    }
}
