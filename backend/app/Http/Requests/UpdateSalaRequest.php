<?php

namespace App\Http\Requests;

use App\Models\Clase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validación del endpoint PUT /api/admin/salas/{id_sala}.
 *
 * Además de las reglas básicas, impide bajar la capacidad de la sala por
 * debajo del cupo de alguna clase ya programada: si no lo hiciéramos,
 * quedarían plazas vendidas "fantasma" (por encima del aforo nuevo).
 */
class UpdateSalaRequest extends FormRequest
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
     * Reglas de validación para actualizar una sala.
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

    /**
     * Validación extra tras las reglas principales.
     *
     * Busca el mayor 'cupo_maximo' entre las clases ya programadas en la
     * sala. Si el admin intenta dejar la capacidad por debajo de ese valor,
     * cortamos la petición con un mensaje claro.
     *
     * @return array<int, \Closure> Cierres de validación aplicados tras rules().
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
