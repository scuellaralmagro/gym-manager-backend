<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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

    public function messages(): array
    {
        return [
            'password.regex' => 'La contraseña debe contener mayúsculas, minúsculas, números y símbolos.',
        ];
    }
}
