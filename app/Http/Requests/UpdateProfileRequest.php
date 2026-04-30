<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Validación del endpoint PUT /api/perfil.
 *
 * Editar el propio perfil. Además de las reglas básicas de datos, valida
 * de forma manual la lógica de cambio de contraseña: el usuario tiene que
 * introducir su contraseña actual y confirmar la nueva.
 */
class UpdateProfileRequest extends FormRequest
{
    /**
     * Autoriza la petición.
     *
     * La ruta ya está protegida por 'auth:sanctum', así que aquí basta con
     * asegurarnos de que exista un usuario autenticado.
     *
     * @return bool true si hay usuario autenticado, false en caso contrario.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Reglas de validación para editar el perfil propio.
     *
     * La regex de 'password_actual' y 'nueva_password' exige al menos una minúscula, una mayúscula,
     * un dígito y un símbolo no alfanumérico.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\Unique>> Reglas por campo.
     */
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

    /**
     * Validaciones adicionales sobre el cambio de contraseña.
     *
     * Si el usuario ha rellenado 'nueva_password', exige también la
     * 'password_actual' correcta y que 'confirmar_password' coincida con
     * la nueva. Si no quiere cambiarla, simplemente deja esos campos vacíos.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator  Instancia del validador Laravel.
     * @return void
     */
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

    /**
     * Mensajes personalizados de error.
     *
     * @return array<string, string> Mensajes por regla.
     */
    public function messages(): array
    {
        return [
            'nueva_password.regex' => 'La contraseña debe contener mayúsculas, minúsculas, números y símbolos.',
        ];
    }
}
