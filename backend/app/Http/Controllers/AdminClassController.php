<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassRequest;
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
}
