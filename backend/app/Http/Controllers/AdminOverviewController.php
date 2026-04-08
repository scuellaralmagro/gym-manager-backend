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
    public function reservas(): AnonymousResourceCollection
    {
        $reservas = Reserva::with(['usuario', 'clase.actividad', 'clase.sala'])
            ->orderByDesc('fecha_creacion')
            ->get();

        return AdminReservaResource::collection($reservas);
    }

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

    public function usuarios(): AnonymousResourceCollection
    {
        $usuarios = Usuario::with('rol')
            ->orderBy('id_rol')
            ->orderBy('nombre')
            ->get();

        return UsuarioResource::collection($usuarios);
    }

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
