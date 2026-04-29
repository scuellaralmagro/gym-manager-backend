<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del endpoint POST /api/admin/usuarios (crear usuario).
 *
 * Exige que la contraseña sea "fuerte" (mayúsculas, minúsculas, números y
 * símbolos) y que el email sea único en la tabla usuarios.
 */
class StoreUserRequest extends FormRequest
{
    /**
     * Autoriza la petición.
     *
     * Ruta protegida por 'role:admin', por eso devolvemos true.
     *
     * @return bool Siempre true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para crear un usuario.
     *
     * La regex de password exige al menos una minúscula, una mayúscula,
     * un dígito y un símbolo no alfanumérico.
     *
     * @return array<string, array<int, string>> Reglas por campo.
     */
    public function rules(): array
    {
        return [
            'nombre'    => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:150'],
            'email'     => ['required', 'string', 'email', 'max:150', 'unique:usuarios,email'],
            'telefono'  => ['nullable', 'string', 'max:20'],
            'id_rol'    => ['required', 'integer', 'exists:roles,id_rol'],
            'password' => [
                'required', 'string', 'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
            ],
        ];
    }

    /**
     * Mensajes personalizados de error.
     *
     * @return array<string, string> Mensajes por regla.
     */
    public function messages(): array
    {
        return [
            'password.regex' => 'La contraseña debe contener mayúsculas, minúsculas, números y símbolos.',
        ];
    }
}
