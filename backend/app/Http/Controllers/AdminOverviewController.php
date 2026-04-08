<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminClaseResource;
use App\Http\Resources\AdminReservaResource;
use App\Http\Resources\UsuarioResource;
use App\Models\Clase;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminOverviewController extends Controller
{
    /**
     * Listar todas las reservas (Admin).
     *
     * Devuelve todas las reservas del sistema con datos del cliente y la clase.
     */
    public function reservas(): AnonymousResourceCollection
    {
        $reservas = Reserva::with(['usuario', 'clase.actividad', 'clase.sala'])
            ->orderByDesc('fecha_creacion')
            ->get();

        return AdminReservaResource::collection($reservas);
    }

    /**
     * Listar todas las clases (Admin).
     *
     * Devuelve las clases con entrenador, actividad, sala y recuento de reservas activas.
     */
    public function clases(): AnonymousResourceCollection
    {
        // Incluyo el conteo de reservas activas para que el admin vea la ocupación
        // de cada clase sin necesidad de una segunda petición
        $clases = Clase::with(['entrenador', 'actividad', 'sala'])
            ->withCount(['reservas as reservas_activas_count' => fn ($q) => $q->where('estado', 'Activa')])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        return AdminClaseResource::collection($clases);
    }

    /**
     * Listar todos los usuarios (Admin).
     *
     * Devuelve los usuarios del sistema con su rol, ordenados por rol y nombre.
     */
    public function usuarios(): AnonymousResourceCollection
    {
        $usuarios = Usuario::with('rol')
            ->orderBy('id_rol')
            ->orderBy('nombre')
            ->get();

        return UsuarioResource::collection($usuarios);
    }

    /**
     * Cancelar cualquier reserva (Admin).
     *
     * Permite al administrador cancelar la reserva de cualquier usuario.
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
