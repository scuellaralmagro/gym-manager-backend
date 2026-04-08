<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_reserva'     => $this->id_reserva,
            'estado'         => $this->estado,
            'fecha_creacion' => $this->fecha_creacion?->toIso8601String(),
            'clase'          => [
                'id_clase'    => $this->clase->id_clase,
                'fecha'       => $this->clase->fecha->toDateString(),
                'hora_inicio' => $this->clase->hora_inicio,
                'hora_fin'    => $this->clase->hora_fin,
                'actividad'   => $this->clase->actividad->nombre,
                'sala'        => $this->clase->sala->nombre,
            ],
        ];
    }
}
