<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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

    public function messages(): array
    {
        return [
            'password.regex' => 'La contraseña debe contener mayúsculas, minúsculas, números y símbolos.',
        ];
    }
}
