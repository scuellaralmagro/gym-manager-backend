<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Sala;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;

class AdminCatalogController extends Controller
{
    /**
     * Catálogos de entrenadores, salas y actividades para los selectores del panel de administración
     */

    public function entrenadores(): JsonResponse
    {
        // id_rol = 2 según la tabla `roles` definida en AGENTS.md.
        $entrenadores = Usuario::where('id_rol', 2)
            ->orderBy('nombre')
            ->orderBy('apellidos')
            ->get(['id_usuario', 'nombre', 'apellidos']);

        return response()->json(['data' => $entrenadores]);
    }

    public function salas(): JsonResponse
    {
        $salas = Sala::orderBy('nombre')->get(['id_sala', 'nombre', 'capacidad_max']);
        return response()->json(['data' => $salas]);
    }

    public function actividades(): JsonResponse
    {
        $actividades = Actividad::orderBy('nombre')
            ->get(['id_actividad', 'nombre', 'descripcion']);
        return response()->json(['data' => $actividades]);
    }
}
