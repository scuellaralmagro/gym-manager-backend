<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de administración de usuarios.
 *
 * Solo lo usa el rol Administrador desde su panel:
 * crear, editar y borrar cuentas de cualquier rol (Admin, Entrenador, Cliente).
 *
 * Todas las rutas quedan bajo /api/admin/usuarios y están protegidas por el middleware 'auth:sanctum' + 'role:admin'.
 */
class AdminUserController extends Controller
{
    /**
     * Crear un nuevo usuario.
     *
     * Recibe los datos ya validados por StoreUserRequest. La contraseña se
     * encripta automáticamente con Hash::make().
     *
     * @param  \App\Http\Requests\StoreUserRequest  $request  Datos validados del nuevo usuario.
     * @return \Illuminate\Http\JsonResponse                  Usuario creado + mensaje (201 Created).
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $usuario = Usuario::create([
            'nombre'        => $data['nombre'],
            'apellidos'     => $data['apellidos'],
            'email'         => $data['email'],
            'telefono'      => $data['telefono'] ?? null,
            'id_rol'        => (int) $data['id_rol'],
            'hash_password' => $data['password'],
        ]);

        return (new UsuarioResource($usuario->fresh('rol')))
            ->additional(['message' => 'Usuario creado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Actualizar los datos completos de un usuario.
     *
     * Permite editar nombre, apellidos, email, teléfono, rol y opcionalmente
     * la contraseña (si se deja vacía se conserva la actual). Bloquea que el
     * administrador autenticado pueda bajarse a sí mismo el rol para no
     * perder acceso al panel.
     *
     * @param  \App\Http\Requests\UpdateUserRequest  $request      Datos validados del usuario.
     * @param  int                                   $id_usuario   ID del usuario a editar.
     * @return \Illuminate\Http\JsonResponse                       Usuario actualizado + mensaje, o 409 si intenta cambiar su propio rol.
     */
    public function update(UpdateUserRequest $request, int $id_usuario): JsonResponse
    {
        $usuario = Usuario::findOrFail($id_usuario);

        $data = $request->validated();

        if (
            (int) $request->user()->id_usuario === (int) $usuario->id_usuario
            && (int) $data['id_rol'] !== (int) $usuario->id_rol
        ) {
            return response()->json([
                'message' => 'No puedes cambiar tu propio rol. Pide a otro administrador que lo haga.',
            ], 409);
        }

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
     * Eliminar un usuario.
     *
     * - El admin autenticado no puede borrarse a sí mismo (se quedaría sin
     *   acceso y perdería su propio token Sanctum).
     * - Si el usuario es un entrenador con clases asignadas, la FK
     *   (ON DELETE RESTRICT) impide el borrado. Capturamos la QueryException
     *   para devolver un 409 con un mensaje legible en vez de un 500 genérico.
     *
     * @param  \Illuminate\Http\Request  $request     Para leer el usuario autenticado.
     * @param  int                       $id_usuario  ID del usuario a borrar.
     * @return \Illuminate\Http\JsonResponse          Mensaje de éxito, o 409 si se autoelimina o tiene clases asignadas.
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
            // PostgreSQL: SQLSTATE 23503 = foreign_key_violation.
            // Comprobamos por prefijo por si el driver lo devuelve como string.
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
