<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClaseResource;
use App\Models\Clase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador del listado público de clases.
 *
 * Cualquier usuario autenticado puede verlo.
 */
class ClassController extends Controller
{
    /**
     * Listar todas las clases con plazas disponibles.
     *
     * Devuelve las clases junto con su actividad, sala y entrenador. Añade
     * el contador de reservas en estado 'Activa' para que el recurso pueda
     * calcular las plazas libres sin sacar cada reserva a mano.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection  Colección de ClaseResource ordenada por fecha y hora.
     */
    public function index(): AnonymousResourceCollection
    {
        $clases = Clase::with(['actividad', 'sala', 'entrenador'])
            ->withCount(['reservas as reservas_activas_count' => fn ($q) => $q->where('estado', 'Activa')])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        return ClaseResource::collection($clases);
    }
}
