<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActividadRequest;
use App\Http\Requests\UpdateActividadRequest;
use App\Models\Actividad;
use App\Models\Clase;
use Illuminate\Http\JsonResponse;

/**
 * CRUD de Actividades para el panel de administración.
 *
 * Las actividades son los "tipos" de clase (Yoga, Pilates, Zumba…). La FK
 * clases.id_actividad usa ON DELETE CASCADE, así que un borrado directo
 * arrastraría clases y reservas. Para no destruir datos sin querer,
 * bloqueamos el borrado si la actividad tiene clases asociadas.
 */
class AdminActividadController extends Controller
{
    /**
     * Crear una nueva actividad.
     *
     * @param  \App\Http\Requests\StoreActividadRequest  $request  Nombre (único) y descripción validados.
     * @return \Illuminate\Http\JsonResponse                       Actividad creada + mensaje (201 Created).
     */
    public function store(StoreActividadRequest $request): JsonResponse
    {
        $actividad = Actividad::create($request->validated());

        return response()->json([
            'message'   => 'Actividad creada correctamente.',
            'actividad' => $actividad,
        ], 201);
    }

    /**
     * Actualizar una actividad existente.
     *
     * @param  \App\Http\Requests\UpdateActividadRequest  $request        Datos validados (unique con ignore del propio id).
     * @param  int                                        $id_actividad   ID de la actividad a editar.
     * @return \Illuminate\Http\JsonResponse                              Actividad actualizada + mensaje (200 OK).
     */
    public function update(UpdateActividadRequest $request, int $id_actividad): JsonResponse
    {
        $actividad = Actividad::findOrFail($id_actividad);
        $actividad->update($request->validated());

        return response()->json([
            'message'   => 'Actividad actualizada correctamente.',
            'actividad' => $actividad->fresh(),
        ]);
    }

    /**
     * Eliminar una actividad con salvaguarda explícita.
     *
     * Contamos primero las clases que usan esta actividad; si hay alguna,
     * devolvemos 409 con un mensaje claro. Así evitamos que el admin borre
     * sin querer todas las clases y reservas asociadas por cascade.
     *
     * @param  int  $id_actividad  ID de la actividad a borrar.
     * @return \Illuminate\Http\JsonResponse  Mensaje de éxito (200), o 409 si tiene clases asociadas.
     */
    public function destroy(int $id_actividad): JsonResponse
    {
        $actividad = Actividad::findOrFail($id_actividad);

        $clasesAsociadas = Clase::where('id_actividad', $id_actividad)->count();
        if ($clasesAsociadas > 0) {
            return response()->json([
                'message' => "No se puede eliminar: la actividad tiene {$clasesAsociadas} clase(s) asociada(s). Reasigna o borra esas clases primero.",
            ], 409);
        }

        $actividad->delete();

        return response()->json([
            'message' => 'Actividad eliminada correctamente.',
        ]);
    }
}
