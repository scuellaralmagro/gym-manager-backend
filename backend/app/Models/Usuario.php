<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'nombre',
        'apellidos',
        'email',
        'telefono',
        'hash_password',
        'id_rol',
    ];

    protected $hidden = [
        'hash_password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'hash_password' => 'hashed',
        ];
    }

    // Le indicamos al sistema de Auth de Laravel que nuestra contraseña se llama "hash_password"
    public function getAuthPasswordName(): string
    {
        return 'hash_password';
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    public function clases()
    {
        return $this->hasMany(Clase::class, 'id_usuario', 'id_usuario');
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'id_usuario', 'id_usuario');
    }
}
