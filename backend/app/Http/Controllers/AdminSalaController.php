<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalaRequest;
use App\Http\Requests\UpdateSalaRequest;
use App\Models\Sala;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class AdminSalaController extends Controller
{
    /**
     * CRUD de Salas para el panel de administración.
     */
    public function store(StoreSalaRequest $request): JsonResponse
    {
        $sala = Sala::create($request->validated());

        return response()->json([
            'message' => 'Sala creada correctamente.',
            'sala'    => $sala,
        ], 201);
    }

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
     * Borrado con salvaguardas.
     *
     * El FK de clases.id_sala usa ON DELETE RESTRICT, así que capturo la
     * QueryException para devolver un 409 legible en lugar
     * del 500 genérico que dejaría escapar la BBDD.
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
