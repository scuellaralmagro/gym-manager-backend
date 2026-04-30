<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Controlador de autenticación
 */
class AuthController extends Controller
{
    /**
     * Iniciar sesión.
     *
     * Busca al usuario por email, comprueba la contraseña y, si coincide,
     * revoca los tokens anteriores y emite uno nuevo. Revocar los anteriores
     * nos asegura que solo haya una sesión activa a la vez por cuenta.
     *
     * @unauthenticated
     *
     * @param  \App\Http\Requests\LoginRequest  $request  Email y contraseña validados.
     * @return \Illuminate\Http\JsonResponse              Token Bearer (200 OK) o mensaje de error (401).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = Usuario::where('email', $request->email)->first();

        if (! $usuario || ! Hash::check($request->password, $usuario->hash_password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        // Revoco tokens anteriores para garantizar una única sesión activa por cuenta
        $usuario->tokens()->delete();

        $token = $usuario->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión correcto.',
            'token'   => $token,
        ]);
    }

    /**
     * Cerrar sesión.
     *
     * Revoca el token Sanctum usado en esta petición
     *
     * @param  \Illuminate\Http\Request  $request  Petición autenticada vía Sanctum.
     * @return \Illuminate\Http\JsonResponse       Mensaje de confirmación (200 OK).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
