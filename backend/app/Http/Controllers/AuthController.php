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
     * Verifico las credenciales de forma manual (sin sesión) para mantener la API
     * completamente stateless, y emito un Personal Access Token de Sanctum.
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
     * Revoco exclusivamente el token que se usó en esta petición,
     * sin afectar a otros dispositivos del mismo usuario.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
