<?php

namespace App\Http\Requests;

use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validación del endpoint POST /api/admin/reservas.
 *
 * El administrador crea una reserva "forzada" para un cliente concreto.
 * Además de comprobar que ambos IDs existen, restringe el id_usuario a
 * cuentas con rol Cliente (solo clientes pueden tener reservas).
 */
class StoreAdminReservationRequest extends FormRequest
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
     * Reglas de validación para crear una reserva desde el panel de admin.
     *
     * @return array<string, array<int, string>> Reglas para 'id_usuario' e 'id_clase'.
     */
    public function rules(): array
    {
        return [
            'id_usuario' => ['required', 'integer', 'exists:usuarios,id_usuario'],
            'id_clase'   => ['required', 'integer', 'exists:clases,id_clase'],
        ];
    }

    /**
     * Validación extra tras las reglas principales.
     *
     * Solo los clientes (id_rol = 3) pueden tener reservas.
     *
     * @return array<int, \Closure> Cierres de validación aplicados tras rules().
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
