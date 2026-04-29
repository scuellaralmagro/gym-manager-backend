<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del endpoint POST /api/login.
 *
 * No requiere autenticación previa, por eso authorize() devuelve true:
 * cualquiera puede intentar iniciar sesión (aunque el rate-limit 'login'
 * corta a 5 intentos por minuto por IP+email).
 */
class LoginRequest extends FormRequest
{
    /**
     * Autoriza la petición.
     *
     * El login siempre se permite a nivel de autorización; el control de
     * credenciales se hace después en el controlador contra la BBDD.
     *
     * @return bool Siempre true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el login.
     *
     * @return array<string, array<int, string>> Reglas para 'email' y 'password'.
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
