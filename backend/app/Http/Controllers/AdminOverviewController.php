<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminClaseResource;
use App\Http\Resources\AdminReservaResource;
use App\Http\Resources\UsuarioResource;
use App\Models\Clase;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * Soporta paginación y filtros server-side (q, id_rol) para que la tabla
     * del frontend no arrastre miles de filas en cada render.
     */
    public function usuarios(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 10);
        // Limito el rango de per_page para que un cliente malicioso no pueda
        // pedir cargas enormes (10k filas) y tumbar la base de datos.
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
