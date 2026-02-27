<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea o actualiza el usuario administrador por defecto.
     * Correo: admin@bch.local | Contraseña: admin123
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@bch.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
            ]
        );

        if ($user->wasRecentlyCreated === false) {
            $user->update([
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
            ]);
        }
    }
}
