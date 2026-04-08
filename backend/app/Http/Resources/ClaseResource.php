<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $plazasOcupadas = $this->reservas_activas_count ?? 0;

        return [
            'id_clase'         => $this->id_clase,
            'fecha'            => $this->fecha->toDateString(),
            'hora_inicio'      => $this->hora_inicio,
            'hora_fin'         => $this->hora_fin,
            'cupo_maximo'      => $this->cupo_maximo,
            'plazas_disponibles' => max(0, $this->cupo_maximo - $plazasOcupadas),
            'actividad'        => $this->actividad->nombre,
            'sala'             => [
                'nombre'        => $this->sala->nombre,
                'capacidad_max' => $this->sala->capacidad_max,
            ],
        ];
    }
}
