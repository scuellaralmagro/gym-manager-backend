<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClaseResource;
use App\Models\Clase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClassController extends Controller
{
    /**
     * Listar todas las clases.
     *
     * Devuelve las clases con actividad, sala y plazas disponibles.
     * Accesible para cualquier usuario autenticado.
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
