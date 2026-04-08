<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    // Mapeo entre el alias corto usado en las rutas y el nombre real almacenado en BD
    private const ROLE_MAP = [
        'admin'      => 'Administrador',
        'entrenador' => 'Entrenador',
        'cliente'    => 'Cliente',
    ];

    public function handle(Request $request, Closure $next, string $role): Response
    {
        $nombreRol = self::ROLE_MAP[$role] ?? $role;

        // Cargo la relación solo si no se ha cargado ya, para evitar consultas redundantes
        $usuario = $request->user();

        // Si el usuario no existe o el rol no coincide, devuelvo un error 403
        if (! $usuario || $usuario->rol->nombre !== $nombreRol) {
            return response()->json([
                'message' => 'No tienes permiso para acceder a este recurso.',
            ], 403);
        }

        return $next($request);
    }
}
