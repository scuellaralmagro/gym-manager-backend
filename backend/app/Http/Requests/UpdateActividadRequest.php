<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del endpoint PUT /api/admin/actividades/{id_actividad}.
 */
class UpdateActividadRequest extends FormRequest
{
    /**
     * Autoriza la petición.
     *
     * Esta ruta es protegida por el middleware role:admin, por lo que siempre devolvemos true.
     *
     * @return bool Siempre true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para actualizar una actividad.
     *
     * El unique usa ignore() para que el propio registro pueda guardarse
     * conservando su nombre sin disparar el error de duplicado.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\Unique>> Reglas por campo.
     */
    public function rules(): array
    {
        $id = (int) $this->route('id_actividad');

        return [
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('actividades', 'nombre')->ignore($id, 'id_actividad'),
            ],
            'descripcion' => ['required', 'string', 'max:1000'],
        ];
    }
}
