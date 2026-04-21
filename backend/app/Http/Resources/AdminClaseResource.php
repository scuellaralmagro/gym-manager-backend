<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminClaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_clase'        => $this->id_clase,
            'fecha'           => $this->fecha->toDateString(),
            'hora_inicio'     => $this->hora_inicio,
            'hora_fin'        => $this->hora_fin,
            'cupo_maximo'     => $this->cupo_maximo,
            'reservas_activas' => $this->reservas_activas_count,
            'entrenador'      => [
                'id_usuario' => $this->entrenador->id_usuario,
                'nombre'     => $this->entrenador->nombre,
                'apellidos'  => $this->entrenador->apellidos,
            ],
            'id_sala'         => $this->id_sala,
            'id_actividad'    => $this->id_actividad,
            'actividad'       => $this->actividad->nombre,
            'sala'            => $this->sala->nombre,
        ];
    }
}
