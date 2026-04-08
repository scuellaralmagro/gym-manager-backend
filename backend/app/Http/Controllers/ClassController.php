<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClaseResource;
use App\Models\Clase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClassController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $clases = Clase::with(['actividad', 'sala'])
            ->withCount(['reservas as reservas_activas_count' => fn ($q) => $q->where('estado', 'Activa')])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        return ClaseResource::collection($clases);
    }
}
