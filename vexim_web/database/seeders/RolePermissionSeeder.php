<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Create permissions (you can expand these)
        $permissions = [
            'view domains',
            'create domains',
            'edit domains',
            'delete domains',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'view email accounts',
            'create email accounts',
            'edit email accounts',
            'delete email accounts',
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
        // Create system-admin role (full access)
        $systemAdmin = Role::firstOrCreate(['name' => 'system-admin']);
        $systemAdmin->givePermissionTo(Permission::all());
        
        // Create domain-admin role (manage their domains)
        $domainAdmin = Role::firstOrCreate(['name' => 'domain-admin']);
        $domainAdmin->givePermissionTo([
            'view domains',
            'edit domains',
            'view users',
            'view email accounts',
            'create email accounts',
            'edit email accounts',
        ]);
        
        // Create domain-user role (read-only)
        $domainUser = Role::firstOrCreate(['name' => 'domain-user']);
        $domainUser->givePermissionTo([
            'view domains',
            'view users',
            'view email accounts',
        ]);
    }
}
