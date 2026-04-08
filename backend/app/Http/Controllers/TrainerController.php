<?php

namespace App\Http\Controllers;

use App\Http\Resources\AsistenciaResource;
use App\Http\Resources\ClaseResource;
use App\Models\Clase;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TrainerController extends Controller
{
    public function agenda(Request $request): AnonymousResourceCollection
    {
        // Obtenemos las clases del entrenador activo, con sus salas y actividades relacionadas,
        // ordenadas por fecha y hora de inicio.
        $clases = Clase::where('id_usuario', $request->user()->id_usuario)
            ->where('fecha', '>=', Carbon::today())
            ->with(['sala', 'actividad'])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        return ClaseResource::collection($clases);
    }

    public function attendance(Request $request, int $id_clase): JsonResponse|AnonymousResourceCollection
    {
        $clase = Clase::find($id_clase);

        if (! $clase) {
            return response()->json(['message' => 'Clase no encontrada.'], 404);
        }

        // Solo el entrenador asignado puede consultar la asistencia de su propia clase
        if ($clase->id_usuario !== $request->user()->id_usuario) {
            abort(403, 'No tienes permiso para ver la asistencia de esta clase.');
        }

        $reservas = Reserva::where('id_clase', $id_clase)
            ->where('estado', 'Activa')
            ->with('usuario')
            ->get();

        if ($reservas->isEmpty()) {
            return response()->json([
                'message' => '0 reservas activas.',
                'data'    => [],
            ]);
        }

        return AsistenciaResource::collection($reservas);
    }
}
