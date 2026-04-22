<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UsuarioResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Obtener perfil del usuario autenticado.
     *
     * Devuelve nombre, apellidos, email, teléfono y rol.
     */
    public function profile(Request $request): UsuarioResource
    {
        $request->user()->load('rol');

        return new UsuarioResource($request->user());
    }

    /**
     * Actualizar el perfil del usuario autenticado
     */
    public function update(UpdateProfileRequest $request): UsuarioResource
    {
        $data    = $request->validated();
        $usuario = $request->user();

        $usuario->nombre    = $data['nombre'];
        $usuario->apellidos = $data['apellidos'];
        $usuario->email     = $data['email'];
        $usuario->telefono  = $data['telefono'] ?? null;

        if (filled($data['nueva_password'] ?? null)) {
            $usuario->hash_password = $data['nueva_password'];
        }

        $usuario->save();

        return new UsuarioResource($usuario->fresh('rol'));
    }
}
