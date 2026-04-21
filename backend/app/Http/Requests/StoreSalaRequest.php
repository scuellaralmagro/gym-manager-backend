<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'        => ['required', 'string', 'max:100'],
            'capacidad_max' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }
}
