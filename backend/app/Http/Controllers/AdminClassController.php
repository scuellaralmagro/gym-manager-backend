<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassRequest;
use App\Http\Requests\UpdateClassRequest;
use App\Models\Clase;
use Illuminate\Http\JsonResponse;

/**
 * CRUD de clases para el panel de administración.
 *
 * Solo el rol Administrador puede crear, editar o borrar clases. Las
 * validaciones de negocio (horarios coherentes, cupo <= capacidad de la
 * sala, entrenador con rol correcto…) se delegan en los FormRequests.
 */
class AdminClassController extends Controller
{
    /**
     * Crear una nueva clase.
     *
     * Registra la sesión con sala, actividad, entrenador y horario ya
     * validados por StoreClassRequest.
     *
     * @param  \App\Http\Requests\StoreClassRequest  $request  Datos validados de la clase.
     * @return \Illuminate\Http\JsonResponse                   Clase creada + mensaje (201 Created).
     */
    public function store(StoreClassRequest $request): JsonResponse
    {
        $clase = Clase::create($request->validated());

        return response()->json([
            'message' => 'Clase creada correctamente.',
            'clase'   => $clase,
        ], 201);
    }

    /**
     * Actualizar una clase existente.
     *
     * Permite actualización parcial gracias a las reglas 'sometimes' del
     * UpdateClassRequest. Si la clase no existe, findOrFail lanza un 404.
     *
     * @param  \App\Http\Requests\UpdateClassRequest  $request   Datos validados (solo los campos enviados).
     * @param  int                                    $id_clase  ID de la clase a editar.
     * @return \Illuminate\Http\JsonResponse                     Clase actualizada + mensaje (200 OK).
     */
    public function update(UpdateClassRequest $request, int $id_clase): JsonResponse
    {
        $clase = Clase::findOrFail($id_clase);
        $clase->update($request->validated());

        return response()->json([
            'message' => 'Clase actualizada correctamente.',
            'clase'   => $clase->fresh(),
        ]);
    }

    /**
     * Eliminar una clase.
     *
     * Borra la clase y, por la relación ON DELETE CASCADE, también sus
     * reservas asociadas en base de datos.
     *
     * @param  int  $id_clase  ID de la clase a borrar.
     * @return \Illuminate\Http\JsonResponse  Mensaje de confirmación (200 OK).
     */
    public function destroy(int $id_clase): JsonResponse
    {
        $clase = Clase::findOrFail($id_clase);
        $clase->delete();

        return response()->json([
            'message' => 'Clase eliminada correctamente.',
        ]);
    }
}
