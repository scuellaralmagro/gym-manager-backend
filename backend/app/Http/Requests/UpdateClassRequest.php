<?php

namespace App\Http\Requests;

use App\Models\Sala;
use App\Models\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validación del endpoint PUT /api/admin/clases/{id_clase}.
 *
 * Hermano de StoreClassRequest pero con reglas 'sometimes' para permitir
 * actualizaciones parciales (solo los campos enviados se validan).
 */
class UpdateClassRequest extends FormRequest
{
    /**
     * Autoriza la petición.
     *
     * Ruta protegida por 'role:admin', por eso devolvemos true aquí.
     *
     * @return bool Siempre true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para actualizar una clase.
     *
     * Todas las claves usan 'sometimes' para aceptar PATCHes parciales.
     * Si se toca 'id_sala', recalculamos el tope de cupo contra la
     * capacidad real de la nueva sala.
     *
     * @return array<string, array<int, string>> Reglas opcionales para cada campo.
     */
    public function rules(): array
    {
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

    /**
     * Validaciones extra tras las reglas principales.
     *
     * Si se envía un nuevo 'id_usuario', comprobamos que sea un Entrenador.
     *
     * @return array<int, \Closure> Cierres de validación aplicados tras rules().
     */
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
