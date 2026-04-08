<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reservas';
    protected $primaryKey = 'id_reserva';

    protected $fillable = [
        'estado',
        'id_usuario',
        'id_clase',
    ];

    protected function casts(): array
    {
        return [
            'fecha_creacion' => 'datetime',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function clase()
    {
        return $this->belongsTo(Clase::class, 'id_clase', 'id_clase');
    }
}
