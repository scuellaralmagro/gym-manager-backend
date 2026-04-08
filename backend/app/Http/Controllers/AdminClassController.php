<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassRequest;
use App\Http\Requests\UpdateClassRequest;
use App\Models\Clase;
use Illuminate\Http\JsonResponse;

class AdminClassController extends Controller
{
    public function store(StoreClassRequest $request): JsonResponse
    {
        $clase = Clase::create($request->validated());

        return response()->json([
            'message' => 'Clase creada correctamente.',
            'clase'   => $clase,
        ], 201);
    }

    public function update(UpdateClassRequest $request, int $id_clase): JsonResponse
    {
        $clase = Clase::findOrFail($id_clase);
        $clase->update($request->validated());

        return response()->json([
            'message' => 'Clase actualizada correctamente.',
            'clase'   => $clase->fresh(),
        ]);
    }

    public function destroy(int $id_clase): JsonResponse
    {
        $clase = Clase::findOrFail($id_clase);
        $clase->delete();

        return response()->json([
            'message' => 'Clase eliminada correctamente.',
        ]);
    }
}
