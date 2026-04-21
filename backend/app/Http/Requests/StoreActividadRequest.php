<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Evita duplicados accidentales en los selectores de "Actividad"
            'nombre'      => ['required', 'string', 'max:100', 'unique:actividades,nombre'],
            'descripcion' => ['required', 'string', 'max:1000'],
        ];
    }
}
