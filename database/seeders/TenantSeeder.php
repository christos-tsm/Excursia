<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class TenantSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Δημιουργία δικαιωμάτων για tenant (τουριστικό γραφείο)
        Permission::create(['name' => 'manage trips', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage guides', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage staff', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage settings', 'guard_name' => 'web']);
        Permission::create(['name' => 'view reports', 'guard_name' => 'web']);
        Permission::create(['name' => 'view trips', 'guard_name' => 'web']);
        Permission::create(['name' => 'lead trips', 'guard_name' => 'web']);

        // Ρόλοι για tenant (τουριστικό γραφείο)
        $ownerRole = Role::create(['name' => 'owner', 'guard_name' => 'web']);
        $guideRole = Role::create(['name' => 'guide', 'guard_name' => 'web']);
        $staffRole = Role::create(['name' => 'staff', 'guard_name' => 'web']);

        // Ανάθεση αδειών στους ρόλους
        $ownerRole->givePermissionTo([
            'manage trips',
            'manage guides',
            'manage staff',
            'manage settings',
            'view reports',
            'view trips',
        ]);

        $guideRole->givePermissionTo([
            'view trips',
            'lead trips',
        ]);

        $staffRole->givePermissionTo([
            'view trips',
        ]);
    }
}
