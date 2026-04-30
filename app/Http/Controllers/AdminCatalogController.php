<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Sala;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;

/**
 * Catálogos ligeros para los selectores del panel de administración.
 *
 * Devuelve los datos necesarios para los selectores del panel de administración.
 */
class AdminCatalogController extends Controller
{
    /**
     * Listar entrenadores para los selectores del panel de administración.
     *
     * Filtra por id_rol = 2 (Entrenador) según la tabla 'roles'. Devuelve
     * solo los campos imprescindibles para construir las opciones del
     * combo (id y nombre).
     *
     * @return \Illuminate\Http\JsonResponse  JSON { data: Array<{id_usuario, nombre, apellidos}> }.
     */
    public function entrenadores(): JsonResponse
    {
        $entrenadores = Usuario::where('id_rol', 2)
            ->orderBy('nombre')
            ->orderBy('apellidos')
            ->get(['id_usuario', 'nombre', 'apellidos']);

        return response()->json(['data' => $entrenadores]);
    }

    /**
     * Listar salas para los selectores del panel de administración.
     *
     * Devuelve todas las salas con su capacidad. El frontend necesita la
     * capacidad para limitar el cupo máximo al crear o editar una clase.
     *
     * @return \Illuminate\Http\JsonResponse  JSON { data: Array<{id_sala, nombre, capacidad_max}> }.
     */
    public function salas(): JsonResponse
    {
        $salas = Sala::orderBy('nombre')->get(['id_sala', 'nombre', 'capacidad_max']);
        return response()->json(['data' => $salas]);
    }

    /**
     * Listar actividades para los selectores del panel de administración.
     *
     * Devuelve todas las actividades con su descripción para que el
     * administrador pueda elegirla al crear o editar clases.
     *
     * @return \Illuminate\Http\JsonResponse  JSON { data: Array<{id_actividad, nombre, descripcion}> }.
     */
    public function actividades(): JsonResponse
    {
        $actividades = Actividad::orderBy('nombre')
            ->get(['id_actividad', 'nombre', 'descripcion']);
        return response()->json(['data' => $actividades]);
    }
}
