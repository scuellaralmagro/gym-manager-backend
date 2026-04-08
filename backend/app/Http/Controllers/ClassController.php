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
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        return ClaseResource::collection($clases);
    }
}
