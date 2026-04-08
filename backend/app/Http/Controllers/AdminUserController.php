<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;

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

        $usuario->update(['id_rol' => $request->validated('id_rol')]);

        return response()->json([
            'message' => 'Rol actualizado correctamente.',
            'usuario' => $usuario->only(['id_usuario', 'nombre', 'apellidos', 'email', 'id_rol']),
        ]);
    }
}
