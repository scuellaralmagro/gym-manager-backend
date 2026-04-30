<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        // Contraseña genérica para el entorno de desarrollo; Bcrypt se aplica vía Hash::make()
        $password = Hash::make('Password1!');

        $usuarios = [
            [
                'nombre'        => 'Sergio',
                'apellidos'     => 'Cuéllar',
                'email'         => 'admin@gymmanager.com',
                'telefono'      => '+34600111222',
                'hash_password' => $password,
                'id_rol'        => 1, // Administrador
            ],
            [
                'nombre'        => 'Laura',
                'apellidos'     => 'Gómez',
                'email'         => 'laura.entrenadora@gymmanager.com',
                'telefono'      => '+34611222333',
                'hash_password' => $password,
                'id_rol'        => 2, // Entrenador
            ],
            [
                'nombre'        => 'Miguel',
                'apellidos'     => 'López',
                'email'         => 'miguel.entrenador@gymmanager.com',
                'telefono'      => '+34622333444',
                'hash_password' => $password,
                'id_rol'        => 2, // Entrenador
            ],
            [
                'nombre'        => 'Ana',
                'apellidos'     => 'Martínez',
                'email'         => 'ana.cliente@email.com',
                'telefono'      => '+34633444555',
                'hash_password' => $password,
                'id_rol'        => 3, // Cliente
            ],
            [
                'nombre'        => 'David',
                'apellidos'     => 'Pérez',
                'email'         => 'david.cliente@email.com',
                'telefono'      => '+34644555666',
                'hash_password' => $password,
                'id_rol'        => 3, // Cliente
            ],
            [
                'nombre'        => 'Sara',
                'apellidos'     => 'Ruiz',
                'email'         => 'sara.cliente@email.com',
                'telefono'      => '+34655666777',
                'hash_password' => $password,
                'id_rol'        => 3, // Cliente
            ],
            [
                'nombre'        => 'Jorge',
                'apellidos'     => 'Sánchez',
                'email'         => 'jorge.cliente@email.com',
                'telefono'      => '+34666777888',
                'hash_password' => $password,
                'id_rol'        => 3, // Cliente
            ],
        ];

        foreach ($usuarios as $usuario) {
            Usuario::updateOrCreate(
                ['email' => $usuario['email']],
                [
                    'nombre' => $usuario['nombre'],
                    'apellidos' => $usuario['apellidos'],
                    'telefono' => $usuario['telefono'],
                    'hash_password' => $usuario['hash_password'],
                    'id_rol' => $usuario['id_rol'],
                ],
            );
        }
    }
}
