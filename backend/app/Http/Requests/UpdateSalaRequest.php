<?php

namespace App\Http\Requests;

use App\Models\Clase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSalaRequest extends FormRequest
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

    /**
     * Evita bajar la capacidad por debajo del cupo de alguna clase ya programada,
     * para evitar plazas "fantasma" imposibles de llenar o ya vendidas.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['nombre', 'capacidad_max'])) {
                    return;
                }

                $idSala = (int) $this->route('id_sala');
                $nuevaCapacidad = (int) $this->input('capacidad_max');

                $cupoMaximoEnClases = Clase::where('id_sala', $idSala)->max('cupo_maximo');

                if ($cupoMaximoEnClases !== null && $nuevaCapacidad < $cupoMaximoEnClases) {
                    $validator->errors()->add(
                        'capacidad_max',
                        "No puedes bajar la capacidad por debajo de {$cupoMaximoEnClases} plazas: ya hay clases programadas con ese cupo."
                    );
                }
            },
        ];
    }
}
