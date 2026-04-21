<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * Cambiar el rol de un usuario (Admin).
     *
     * Asigna un nuevo rol al usuario indicado por su ID.
     */
    public function updateRole(UpdateUserRoleRequest $request, int $id_usuario): JsonResponse
    {
        $usuario = Usuario::findOrFail($id_usuario);
        $nuevoRol = (int) $request->validated('id_rol');

        // Evitamos que el admin se baje a sí mismo el rol
        if (
            (int) $request->user()->id_usuario === (int) $usuario->id_usuario
            && $nuevoRol !== (int) $usuario->id_rol
        ) {
            return response()->json([
                'message' => 'No puedes cambiar tu propio rol. Pide a otro administrador que lo haga.',
            ], 409);
        }

        $usuario->update(['id_rol' => $nuevoRol]);

        return response()->json([
            'message' => 'Rol actualizado correctamente.',
            'usuario' => $usuario->only(['id_usuario', 'nombre', 'apellidos', 'email', 'id_rol']),
        ]);
    }

    /**
     * Actualizar los datos completos de un usuario (Admin).
     *
     * Permite editar nombre, apellidos, email, teléfono, rol y opcionalmente la
     * contraseña (si el admin la deja vacía se conserva la actual).
     */
    public function update(UpdateUserRequest $request, int $id_usuario): JsonResponse
    {
        $usuario = Usuario::findOrFail($id_usuario);

        $data = $request->validated();

        // Evitamos que el admin se baje a sí mismo el rol dentro del edit completo
        if (
            (int) $request->user()->id_usuario === (int) $usuario->id_usuario
            && (int) $data['id_rol'] !== (int) $usuario->id_rol
        ) {
            return response()->json([
                'message' => 'No puedes cambiar tu propio rol. Pide a otro administrador que lo haga.',
            ], 409);
        }

        // La contraseña solo se toca si el admin la ha introducido. El cast
        // `hashed` del modelo se encarga del Bcrypt transparentemente.
        if (! empty($data['password'])) {
            $data['hash_password'] = $data['password'];
        }
        unset($data['password']);

        $usuario->update($data);

        return (new UsuarioResource($usuario->fresh('rol')))
            ->additional(['message' => 'Usuario actualizado correctamente.'])
            ->response();
    }

    /**
     * Eliminar un usuario (Admin).
     *
     * Salvaguardas:
     *  - El admin autenticado no puede borrarse a sí mismo (dejaría el sistema
     *    sin control de acceso y además perdería su propio token Sanctum).
     *  - Si el usuario es un entrenador con clases asignadas, la FK
     *    (ON DELETE RESTRICT) impide el borrado; lo capturo para devolver un
     *    409 legible en vez de un 500 genérico.
     */
    public function destroy(Request $request, int $id_usuario): JsonResponse
    {
        $usuario = Usuario::findOrFail($id_usuario);

        if ((int) $request->user()->id_usuario === (int) $usuario->id_usuario) {
            return response()->json([
                'message' => 'No puedes eliminar tu propia cuenta de administrador.',
            ], 409);
        }

        try {
            $usuario->delete();
        } catch (QueryException $e) {
            // PostgreSQL: 23503 = foreign_key_violation. Nos protegemos también
            // por prefijo SQLSTATE por si el driver lo devuelve como string.
            if (str_starts_with((string) $e->getCode(), '23')) {
                return response()->json([
                    'message' => 'No se puede eliminar: el usuario tiene clases asignadas. Reasigna o elimina sus clases primero.',
                ], 409);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }
}
