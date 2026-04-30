<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sala extends Model
{
    protected $table = 'salas';
    protected $primaryKey = 'id_sala';

    protected $fillable = ['nombre', 'capacidad_max'];

    public function clases()
    {
        return $this->hasMany(Clase::class, 'id_sala', 'id_sala');
    }
}
