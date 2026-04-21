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
     * Soporta paginación y filtros server-side (q, estado, id_clase).
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
     * Listar todas las clases (Admin).
     *
     * Soporta paginación, filtros (fecha_desde, fecha_hasta, id_usuario) y
     * ordenación server-side para que la tabla del admin escale cuando la
     * oferta crezca y no arrastre miles de filas en cada render.
     */
    public function clases(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        $fechaDesde = $request->query('fecha_desde');
        $fechaHasta = $request->query('fecha_hasta');
        $idUsuario  = $request->query('id_usuario');

        // Whitelist de columnas ordenables para evitar inyecciones de SQL
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
