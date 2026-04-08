<?php

namespace App\Http\Requests;

use App\Models\Sala;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Utilizamos sometimes para permitir actualizaciones parciales en el mismo endpoint
        $capacidadMax = null;

        if ($this->filled('id_sala')) {
            $sala = Sala::find($this->input('id_sala'));
            $capacidadMax = $sala?->capacidad_max;
        }

        return [
            'fecha'        => ['sometimes', 'date'],
            'hora_inicio'  => ['sometimes', 'date_format:H:i'],
            'hora_fin'     => ['sometimes', 'date_format:H:i', 'after:hora_inicio'],
            'cupo_maximo'  => [
                'sometimes',
                'integer',
                'min:1',
                $capacidadMax ? "max:{$capacidadMax}" : 'max:999',
            ],
            'id_sala'      => ['sometimes', 'integer', 'exists:salas,id_sala'],
            'id_usuario'   => ['sometimes', 'integer', 'exists:usuarios,id_usuario'],
            'id_actividad' => ['sometimes', 'integer', 'exists:actividades,id_actividad'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->filled('id_usuario') || $validator->errors()->has('id_usuario')) {
                    return;
                }

                $usuario = Usuario::find($this->input('id_usuario'));

                if ($usuario && $usuario->id_rol !== 2) {
                    $validator->errors()->add(
                        'id_usuario',
                        'El usuario asignado debe tener el rol de Entrenador.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'cupo_maximo.max' => 'El cupo máximo no puede superar la capacidad de la sala seleccionada (:max plazas).',
            'hora_fin.after'  => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }
}
