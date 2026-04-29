<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UsuarioResource;
use Illuminate\Http\Request;

/**
 * Controlador del perfil del usuario autenticado.
 *
 * Cualquier usuario (sea del rol que sea) puede consultar y editar sus propios datos. 
 * Para gestión de cuentas ajenas se usa AdminUserController.
 */
class UserController extends Controller
{
    /**
     * Obtener el perfil del usuario autenticado.
     *
     * Devuelve nombre, apellidos, email, teléfono y rol. Cargamos la
     * relación 'rol' para que UsuarioResource pueda incluir su nombre
     * legible ("Administrador", "Entrenador" o "Cliente").
     *
     * @param  \Illuminate\Http\Request  $request  Petición con el usuario autenticado.
     * @return \App\Http\Resources\UsuarioResource  Recurso con los datos del usuario.
     */
    public function profile(Request $request): UsuarioResource
    {
        $request->user()->load('rol');

        return new UsuarioResource($request->user());
    }

    /**
     * Actualizar el perfil del usuario autenticado.
     *
     * Actualiza nombre, apellidos, email y teléfono. Además, si el usuario
     * ha rellenado los campos de contraseña, también se cambia el hash.
     *
     * @param  \App\Http\Requests\UpdateProfileRequest  $request  Datos validados del perfil.
     * @return \App\Http\Resources\UsuarioResource      Perfil actualizado.
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
