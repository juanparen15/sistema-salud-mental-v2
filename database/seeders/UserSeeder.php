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
            'email' => 'sistemas@puertoboyaca-boyaca.gov.co',
            'password' => Hash::make('Sistemas2025*'),
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Crear usuario profesional de ejemplo
        User::create([
            'name' => 'Claudia Ciro',
            'email' => 'salud@puertoboyaca-boyaca.gov.co',
            'password' => Hash::make('Salud2025'), // Cambiar en producción
            'role' => 'coordinador',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Usuarios de ejemplo creados exitosamente');
        $this->command->info('📧 Email: sistemas@puertoboyaca-boyaca.gov.co');
        $this->command->info('🔑 Password: Sistemas2025*');
        $this->command->warn('⚠️  IMPORTANTE: Cambiar contraseña en producción');
    }
}
