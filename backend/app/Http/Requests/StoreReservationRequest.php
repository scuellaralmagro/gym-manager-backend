<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del endpoint POST /api/reservas.
 *
 * Solo validamos que el id_clase recibido sea un entero que exista en la tabla de clases.
 */
class StoreReservationRequest extends FormRequest
{
    /**
     * Autoriza la petición.
     *
     * La ruta ya está protegida por 'auth:sanctum' + 'role:cliente', así
     * que si llegamos hasta aquí podemos dar por autorizada la petición.
     *
     * @return bool Siempre true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para crear una reserva.
     *
     * @return array<string, array<int, string>> Reglas para 'id_clase'.
     */
    public function rules(): array
    {
        return [
            'id_clase' => ['required', 'integer', 'exists:clases,id_clase'],
        ];
    }
}
