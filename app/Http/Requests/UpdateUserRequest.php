<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del endpoint PUT /api/admin/usuarios/{id_usuario}.
 *
 * Se usa para que un administrador edite los datos de cualquier usuario.
 * La contraseña es opcional (si se deja vacía se conserva la actual).
 */
class UpdateUserRequest extends FormRequest
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
     * Reglas de validación para actualizar un usuario.
     *
     * El email usa Rule::unique con ignore() para permitir que el propio
     * usuario mantenga su email al guardarse.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\Unique>> Reglas por campo.
     */
    public function rules(): array
    {
        $id = (int) $this->route('id_usuario');

        return [
            'nombre'    => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:150'],
            'email'     => [
                'required', 'string', 'email', 'max:150',
                Rule::unique('usuarios', 'email')->ignore($id, 'id_usuario'),
            ],
            'telefono'  => ['nullable', 'string', 'max:20'],
            'id_rol'    => ['required', 'integer', 'exists:roles,id_rol'],
            'password'  => [
                'nullable', 'string', 'min:8',
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
