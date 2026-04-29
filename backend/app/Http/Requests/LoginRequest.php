<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del endpoint POST /api/login.
 *
 * Valida el email y la contraseña.
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
