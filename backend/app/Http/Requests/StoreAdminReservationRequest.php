<?php

namespace App\Http\Requests;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAdminReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_usuario' => ['required', 'integer', 'exists:usuarios,id_usuario'],
            'id_clase'   => ['required', 'integer', 'exists:clases,id_clase'],
        ];
    }

    /**
     * Solo los clientes (id_rol = 3) pueden tener reservas.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('id_usuario')) {
                    return;
                }

                $usuario = Usuario::find($this->input('id_usuario'));

                if ($usuario && $usuario->id_rol !== 3) {
                    $validator->errors()->add(
                        'id_usuario',
                        'Solo los usuarios con rol Cliente pueden tener reservas.'
                    );
                }
            },
        ];
    }
}
