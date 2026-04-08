<?php

namespace App\Http\Requests;

use App\Models\Sala;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Resuelvo el tope dinámico del cupo a partir de la sala seleccionada,
        // ya que cupo_maximo nunca debe superar la capacidad física de la sala
        $capacidadMax = null;

        if ($this->filled('id_sala')) {
            $sala = Sala::find($this->input('id_sala'));
            $capacidadMax = $sala?->capacidad_max;
        }

        return [
            'fecha'        => ['required', 'date'],
            'hora_inicio'  => ['required', 'date_format:H:i'],
            'hora_fin'     => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'cupo_maximo'  => [
                'required',
                'integer',
                'min:1',
                $capacidadMax ? "max:{$capacidadMax}" : 'max:999',
            ],
            'id_sala'      => ['required', 'integer', 'exists:salas,id_sala'],
            'id_usuario'   => ['required', 'integer', 'exists:usuarios,id_usuario'],
            'id_actividad' => ['required', 'integer', 'exists:actividades,id_actividad'],
        ];
    }

    /**
     * Valido que el usuario asignado tenga rol Entrenador (id_rol = 2)
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('id_usuario')) {
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
