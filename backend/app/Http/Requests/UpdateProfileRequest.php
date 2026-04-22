<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
        // La acción se permite a cualquier usuario ya que la ruta ya está protegida por sanctum
    }

    public function rules(): array
    {
        $id = (int) $this->user()->id_usuario;

        return [
            'nombre'    => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:150'],
            'email'     => [
                'required', 'string', 'email', 'max:150',
                Rule::unique('usuarios', 'email')->ignore($id, 'id_usuario'),
            ],
            'telefono'  => ['nullable', 'string', 'max:20'],
            'password_actual'    => ['nullable', 'string'],
            'nueva_password'     => [
                'nullable', 'string', 'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
            ],
            'confirmar_password' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $nueva = $this->input('nueva_password');
            if (! filled($nueva)) {
                return;
            }

            $actual = $this->input('password_actual');
            if (! filled($actual)) {
                $v->errors()->add(
                    'password_actual',
                    'Debes introducir tu contraseña actual para cambiarla.',
                );
            } elseif (! Hash::check($actual, $this->user()->hash_password)) {
                // Comprobamos que la contraseña actual es correcta antes de cambiarla
                $v->errors()->add(
                    'password_actual',
                    'La contraseña actual no es correcta.',
                );
            }

            $confirmar = $this->input('confirmar_password');
            if ($confirmar !== $nueva) {
                $v->errors()->add(
                    'confirmar_password',
                    'La confirmación no coincide con la nueva contraseña.',
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'nueva_password.regex' => 'La contraseña debe contener mayúsculas, minúsculas, números y símbolos.',
        ];
    }
}
