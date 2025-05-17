<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Δημιουργία δικαιωμάτων - landlord (κεντρική διαχείριση)
        // Δικαιώματα για tenants (τουριστικά γραφεία)
        Permission::create(['name' => 'view tenants', 'guard_name' => 'web']);
        Permission::create(['name' => 'create tenants', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit tenants', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete tenants', 'guard_name' => 'web']);
        Permission::create(['name' => 'approve tenants', 'guard_name' => 'web']);

        // Δικαιώματα για tenant (τουριστικό γραφείο)
        Permission::create(['name' => 'manage trips', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage guides', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage staff', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage settings', 'guard_name' => 'web']);
        Permission::create(['name' => 'view reports', 'guard_name' => 'web']);
        Permission::create(['name' => 'view trips', 'guard_name' => 'web']);
        Permission::create(['name' => 'lead trips', 'guard_name' => 'web']);

        // Ρόλοι - landlord (κεντρική διαχείριση)
        $superAdminRole = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);

        // Ρόλοι - tenant (τουριστικό γραφείο)
        $ownerRole = Role::create(['name' => 'owner', 'guard_name' => 'web']);
        $guideRole = Role::create(['name' => 'guide', 'guard_name' => 'web']);
        $staffRole = Role::create(['name' => 'staff', 'guard_name' => 'web']);

        // Ανάθεση αδειών στους ρόλους
        $superAdminRole->givePermissionTo(Permission::all());

        $adminRole->givePermissionTo([
            'view tenants',
            'create tenants',
            'edit tenants',
            'delete tenants',
            'approve tenants',
        ]);

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
