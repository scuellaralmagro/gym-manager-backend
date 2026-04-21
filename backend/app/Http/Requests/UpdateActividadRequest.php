<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
