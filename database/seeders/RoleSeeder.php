<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $coordinador = Role::firstOrCreate(['name' => 'coordinador']);
        $profesional = Role::firstOrCreate(['name' => 'profesional']);
        $auxiliar = Role::firstOrCreate(['name' => 'auxiliar']);
        
        // Obtener todos los permisos
        $allPermissions = Permission::all();
        
        // Super Admin: todos los permisos
        $superAdmin->syncPermissions($allPermissions);
        
        // Coordinador: gestión completa excepto configuración de roles
        $coordinadorPermissions = $allPermissions->filter(function ($permission) {
            return !str_contains($permission->name, 'role');
        });
        $coordinador->syncPermissions($coordinadorPermissions);
        
        // Profesional: crear y editar casos, no eliminar
        $profesionalPermissions = $allPermissions->filter(function ($permission) {
            return !str_contains($permission->name, 'delete') &&
                   !str_contains($permission->name, 'force_delete') &&
                   !str_contains($permission->name, 'role');
        });
        $profesional->syncPermissions($profesionalPermissions);
        
        // Auxiliar: solo lectura y seguimientos
        $auxiliarPermissions = $allPermissions->filter(function ($permission) {
            return str_contains($permission->name, 'view') ||
                   str_contains($permission->name, 'monthly_followup');
        });
        $auxiliar->syncPermissions($auxiliarPermissions);
    }
}