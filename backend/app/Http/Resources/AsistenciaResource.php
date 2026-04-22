<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsistenciaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_reserva' => $this->id_reserva,
            'estado'     => $this->estado,
            'cliente'    => [
                'id_usuario' => $this->usuario->id_usuario,
                'nombre'     => $this->usuario->nombre,
                'apellidos'  => $this->usuario->apellidos,
                'email'      => $this->usuario->email,
            ],
        ];
    }
}
