<?php
// ================================
// ARCHIVO: database/seeders/UserSeeder.php
// SEEDER PARA CREAR USUARIO ADMINISTRADOR INICIAL
// ================================

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador por defecto
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@saludmental.local',
            'password' => Hash::make('password'), // Cambiar en producción
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Crear usuario profesional de ejemplo
        User::create([
            'name' => 'Dr. Juan Pérez',
            'email' => 'profesional@saludmental.local',
            'password' => Hash::make('password'), // Cambiar en producción
            'role' => 'professional',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Crear coordinador de ejemplo
        User::create([
            'name' => 'María García',
            'email' => 'coordinador@saludmental.local',
            'password' => Hash::make('password'), // Cambiar en producción
            'role' => 'coordinator',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Usuarios de ejemplo creados exitosamente');
        $this->command->info('📧 Email: admin@saludmental.local');
        $this->command->info('🔑 Password: password');
        $this->command->warn('⚠️  IMPORTANTE: Cambiar contraseña en producción');
    }
}
