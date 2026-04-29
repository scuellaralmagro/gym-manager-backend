<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del endpoint POST /api/admin/actividades (crear actividad).
 */
class StoreActividadRequest extends FormRequest
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
     * Reglas de validación para crear una actividad.
     *
     * El nombre es único en la tabla 'actividades' para evitar duplicados
     * accidentales en los selectores del frontend.
     *
     * @return array<string, array<int, string>> Reglas para 'nombre' y 'descripcion'.
     */
    public function rules(): array
    {
        return [
            'nombre'      => ['required', 'string', 'max:100', 'unique:actividades,nombre'],
            'descripcion' => ['required', 'string', 'max:1000'],
        ];
    }
}
