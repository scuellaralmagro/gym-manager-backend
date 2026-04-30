<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_usuario' => $this->id_usuario,
            'nombre'     => $this->nombre,
            'apellidos'  => $this->apellidos,
            'email'      => $this->email,
            'telefono'   => $this->telefono,
            'rol'        => $this->rol->nombre,
        ];
    }
}
