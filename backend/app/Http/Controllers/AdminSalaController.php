<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalaRequest;
use App\Http\Requests\UpdateSalaRequest;
use App\Models\Sala;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

/**
 * CRUD de Salas para el panel de administración.
 *
 * Aquí se dan de alta, se editan y se borran salas. Una sala no se puede
 * eliminar si tiene clases programadas (la FK usa ON DELETE RESTRICT) y su
 * capacidad no se puede bajar por debajo del mayor cupo ya asignado
 * (esa regla la impone UpdateSalaRequest).
 */
class AdminSalaController extends Controller
{
    /**
     * Crear una nueva sala.
     *
     * @param  \App\Http\Requests\StoreSalaRequest  $request  Nombre y capacidad_max validados.
     * @return \Illuminate\Http\JsonResponse                  Sala creada + mensaje (201 Created).
     */
    public function store(StoreSalaRequest $request): JsonResponse
    {
        $sala = Sala::create($request->validated());

        return response()->json([
            'message' => 'Sala creada correctamente.',
            'sala'    => $sala,
        ], 201);
    }

    /**
     * Actualizar una sala existente.
     *
     * @param  \App\Http\Requests\UpdateSalaRequest  $request  Nombre y capacidad_max validados (incluye la salvaguarda de capacidad mínima).
     * @param  int                                   $id_sala  ID de la sala a editar.
     * @return \Illuminate\Http\JsonResponse                   Sala actualizada + mensaje (200 OK).
     */
    public function update(UpdateSalaRequest $request, int $id_sala): JsonResponse
    {
        $sala = Sala::findOrFail($id_sala);
        $sala->update($request->validated());

        return response()->json([
            'message' => 'Sala actualizada correctamente.',
            'sala'    => $sala->fresh(),
        ]);
    }

    /**
     * Eliminar una sala.
     *
     * La FK clases.id_sala tiene ON DELETE RESTRICT, así que si la sala
     * tiene clases programadas PostgreSQL lanzará una QueryException con
     * SQLSTATE 23503. Capturamos esa excepción para devolver un 409 con un
     * mensaje legible en vez del 500 genérico.
     *
     * @param  int  $id_sala  ID de la sala a borrar.
     * @return \Illuminate\Http\JsonResponse  Mensaje de éxito (200) o 409 si tiene clases asociadas.
     */
    public function destroy(int $id_sala): JsonResponse
    {
        $sala = Sala::findOrFail($id_sala);

        try {
            $sala->delete();
        } catch (QueryException $e) {
            if (str_starts_with((string) $e->getCode(), '23')) {
                return response()->json([
                    'message' => 'No se puede eliminar: la sala tiene clases programadas. Reasigna o borra sus clases primero.',
                ], 409);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Sala eliminada correctamente.',
        ]);
    }
}
