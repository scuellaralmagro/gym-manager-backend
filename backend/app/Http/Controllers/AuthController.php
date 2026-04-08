<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Iniciar sesión.
     *
     * Autentica al usuario con email y contraseña, revoca tokens anteriores
     * y devuelve un nuevo Personal Access Token (Sanctum).
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = Usuario::where('email', $request->email)->first();

        if (! $usuario || ! Hash::check($request->password, $usuario->hash_password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        // Revoco tokens anteriores para garantizar una única sesión activa por dispositivo
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
     * Revoca el token utilizado en esta petición.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
