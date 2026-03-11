<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@demo.test'],
            [
                'name' => 'Amministratore',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN,
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'operatore@demo.test'],
            [
                'name' => 'Mario Rossi',
                'password' => Hash::make('password'),
                'role' => UserRole::OPERATORE,
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'tecnico@demo.test'],
            [
                'name' => 'Giuseppe Verdi',
                'password' => Hash::make('password'),
                'role' => UserRole::OPERATORE,
                'is_active' => true,
            ]
        );
    }
}
