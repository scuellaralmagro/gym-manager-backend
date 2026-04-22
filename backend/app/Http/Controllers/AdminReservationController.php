<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminReservationRequest;
use App\Http\Resources\AdminReservaResource;
use App\Models\Reserva;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminReservationController extends Controller
{
    /**
     * Forzar la creación de una reserva desde el panel de administración.
     */
    public function store(StoreAdminReservationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $idUsuario = (int) $data['id_usuario'];
        $idClase   = (int) $data['id_clase'];

        $reserva = DB::transaction(function () use ($idUsuario, $idClase) {
            // lockForUpdate bloquea la fila si existía, evitando problemas
            // con cancelaciones concurrentes sobre la misma (cliente, clase)
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
