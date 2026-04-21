<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActividadRequest;
use App\Http\Requests\UpdateActividadRequest;
use App\Models\Actividad;
use App\Models\Clase;
use Illuminate\Http\JsonResponse;

class AdminActividadController extends Controller
{
    /**
     * CRUD de Actividades para el panel de administración.
     */
    public function store(StoreActividadRequest $request): JsonResponse
    {
        $actividad = Actividad::create($request->validated());

        return response()->json([
            'message'   => 'Actividad creada correctamente.',
            'actividad' => $actividad,
        ], 201);
    }

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
     * Borrado con salvaguarda explícita.
     *
     * El FK de clases.id_actividad usa ON DELETE CASCADE, lo que en la práctica
     * arrastraría clases y reservas (cascade en Reservas) sin confirmación. Para
     * no destruir datos silenciosamente, bloqueamos el borrado si hay clases activas.
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
