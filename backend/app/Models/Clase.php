<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clase extends Model
{
    protected $table = 'clases';
    protected $primaryKey = 'id_clase';

    protected $fillable = [
        'fecha',
        'hora_inicio',
        'hora_fin',
        'cupo_maximo',
        'id_sala',
        'id_usuario',
        'id_actividad',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function sala()
    {
        return $this->belongsTo(Sala::class, 'id_sala', 'id_sala');
    }

    // El entrenador responsable de la clase
    public function entrenador()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function actividad()
    {
        return $this->belongsTo(Actividad::class, 'id_actividad', 'id_actividad');
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'id_clase', 'id_clase');
    }
}
