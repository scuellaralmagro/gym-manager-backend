<?php

namespace App\Http\Requests;

use App\Models\Sala;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validación del endpoint POST /api/admin/clases (crear clase).
 *
 * Aplica dos reglas de negocio propias:
 *  - El cupo máximo no puede superar la capacidad de la sala elegida.
 *  - El usuario asignado tiene que tener rol Entrenador (id_rol = 2).
 */
class StoreClassRequest extends FormRequest
{
    /**
     * Autoriza la petición.
     *
     * La ruta ya está protegida por 'role:admin', así que aquí devolvemos
     * true sin más comprobaciones.
     *
     * @return bool Siempre true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para crear una clase.
     *
     * El tope de 'cupo_maximo' se calcula dinámicamente a partir de la
     * capacidad de la sala seleccionada, para que nunca se pueda ofertar
     * más plazas que las físicas de la sala.
     *
     * @return array<string, array<int, string>> Reglas para fecha, horas, cupo, sala, entrenador y actividad.
     */
    public function rules(): array
    {
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
     * Validaciones extra tras las reglas principales.
     *
     * Comprueba que el usuario asignado tiene rol Entrenador (id_rol = 2).
     * Lo hacemos aquí (no en rules()) porque necesitamos la info del usuario
     * una vez pasada la validación básica de 'exists'.
     *
     * @return array<int, \Closure> Cierres de validación aplicados tras rules().
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

    /**
     * Mensajes personalizados de error.
     *
     * @return array<string, string> Mensajes por regla.
     */
    public function messages(): array
    {
        return [
            'cupo_maximo.max' => 'El cupo máximo no puede superar la capacidad de la sala seleccionada (:max plazas).',
            'hora_fin.after'  => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }
}
