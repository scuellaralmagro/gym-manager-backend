<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del endpoint POST /api/admin/salas (crear sala).
 */
class StoreSalaRequest extends FormRequest
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
     * Reglas de validación para crear una sala.
     *
     * @return array<string, array<int, string>> Reglas para 'nombre' y 'capacidad_max'.
     */
    public function rules(): array
    {
        return [
            'nombre'        => ['required', 'string', 'max:100'],
            'capacidad_max' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }
}
