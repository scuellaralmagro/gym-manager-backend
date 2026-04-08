<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    protected $table = 'actividades';
    protected $primaryKey = 'id_actividad';

    protected $fillable = ['nombre', 'descripcion'];

    public function clases()
    {
        return $this->hasMany(Clase::class, 'id_actividad', 'id_actividad');
    }
}
