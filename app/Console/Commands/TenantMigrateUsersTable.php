<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class TenantMigrateUsersTable extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:migrate-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate users and permissions tables to all active tenants';

    /**
     * Execute the console command.
     */
    public function handle() {
        $tenants = Tenant::where('is_active', true)->get();
        $this->info("Βρέθηκαν " . $tenants->count() . " ενεργοί tenants.");

        foreach ($tenants as $tenant) {
            $this->info("Εκτέλεση migrations για tenant #{$tenant->id} ({$tenant->name}) στη βάση '{$tenant->database}'...");

            try {
                // Έλεγχος αν η βάση υπάρχει
                $databaseExists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$tenant->database}'");

                if (empty($databaseExists)) {
                    $this->error("Η βάση δεδομένων '{$tenant->database}' δεν υπάρχει. Δημιουργία νέας βάσης...");
                    DB::statement("CREATE DATABASE IF NOT EXISTS `{$tenant->database}`");
                }

                // Makings tenant current to perform operations
                $tenant->makeCurrent();

                // Εκτέλεση του migration για τον πίνακα cache πρώτα
                $this->info("Εκτέλεση cache table migration...");
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/tenant/2024_06_24_000000_create_cache_table.php',
                    '--database' => 'tenant',
                    '--force' => true
                ]);

                // Εκτέλεση του migration για τον πίνακα jobs
                $this->info("Εκτέλεση jobs table migration...");
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/tenant/2024_06_24_000001_create_jobs_table.php',
                    '--database' => 'tenant',
                    '--force' => true
                ]);

                // Εκτέλεση των migrations για τους users
                $this->info("Εκτέλεση users migrations...");
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/tenant/2014_10_12_000000_create_users_table.php',
                    '--database' => 'tenant',
                    '--force' => true
                ]);

                // Εκτέλεση των migrations για τα permissions
                $this->info("Εκτέλεση permissions migrations...");
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/tenant/2024_06_23_000002_create_permission_tables.php',
                    '--database' => 'tenant',
                    '--force' => true
                ]);

                // Δημιουργία των ρόλων και δικαιωμάτων απευθείας
                $this->info("Δημιουργία ρόλων και δικαιωμάτων...");

                // Reset cached roles and permissions
                app()[PermissionRegistrar::class]->forgetCachedPermissions();

                // Δημιουργία δικαιωμάτων για tenant (τουριστικό γραφείο)
                // Ελέγχουμε αν το δικαίωμα υπάρχει ήδη πριν το δημιουργήσουμε
                $permissions = [
                    'manage trips',
                    'manage guides',
                    'manage staff',
                    'manage settings',
                    'view reports',
                    'view trips',
                    'lead trips'
                ];

                $permissionIds = [];

                foreach ($permissions as $permName) {
                    if (!Permission::where('name', $permName)->exists()) {
                        $perm = Permission::create(['name' => $permName, 'guard_name' => 'web']);
                        $permissionIds[$permName] = $perm->id;
                        $this->info("Δημιουργήθηκε το δικαίωμα: {$permName}");
                    } else {
                        $permissionIds[$permName] = Permission::where('name', $permName)->first()->id;
                    }
                }

                // Ρόλοι για tenant (τουριστικό γραφείο)
                $roles = ['owner', 'guide', 'staff'];
                $roleIds = [];

                foreach ($roles as $roleName) {
                    if (!Role::where('name', $roleName)->exists()) {
                        $role = Role::create(['name' => $roleName, 'guard_name' => 'web']);
                        $roleIds[$roleName] = $role->id;
                        $this->info("Δημιουργήθηκε ο ρόλος: {$roleName}");
                    } else {
                        $roleIds[$roleName] = Role::where('name', $roleName)->first()->id;
                    }
                }

                // Ανάθεση δικαιωμάτων για τον owner
                if (isset($roleIds['owner'])) {
                    $ownerPermissions = ['manage trips', 'manage guides', 'manage staff', 'manage settings', 'view reports', 'view trips'];
                    foreach ($ownerPermissions as $perm) {
                        if (isset($permissionIds[$perm])) {
                            // Προσθέτουμε απευθείας στον πίνακα role_has_permissions
                            DB::table('role_has_permissions')->insertOrIgnore([
                                'permission_id' => $permissionIds[$perm],
                                'role_id' => $roleIds['owner']
                            ]);
                        }
                    }
                    $this->info("Ανατέθηκαν δικαιώματα στον ρόλο: owner");
                }

                // Ανάθεση δικαιωμάτων για τον guide
                if (isset($roleIds['guide'])) {
                    $guidePermissions = ['view trips', 'lead trips'];
                    foreach ($guidePermissions as $perm) {
                        if (isset($permissionIds[$perm])) {
                            DB::table('role_has_permissions')->insertOrIgnore([
                                'permission_id' => $permissionIds[$perm],
                                'role_id' => $roleIds['guide']
                            ]);
                        }
                    }
                    $this->info("Ανατέθηκαν δικαιώματα στον ρόλο: guide");
                }

                // Ανάθεση δικαιωμάτων για τον staff
                if (isset($roleIds['staff'])) {
                    if (isset($permissionIds['view trips'])) {
                        DB::table('role_has_permissions')->insertOrIgnore([
                            'permission_id' => $permissionIds['view trips'],
                            'role_id' => $roleIds['staff']
                        ]);
                    }
                    $this->info("Ανατέθηκαν δικαιώματα στον ρόλο: staff");
                }

                // Εκτέλεση του migration για τον πίνακα invitations
                $this->info("Εκτέλεση invitations table migration...");
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/tenant/2024_06_24_000002_create_invitations_table.php',
                    '--database' => 'tenant',
                    '--force' => true
                ]);

                // Εκτέλεση των υπόλοιπων migrations
                $this->info("Εκτέλεση υπόλοιπων migrations...");
                Artisan::call('migrate', [
                    '--path' => 'database/migrations/tenant',
                    '--database' => 'tenant',
                    '--force' => true
                ]);

                $tenant->forgetCurrent();
                $this->info("Ολοκληρώθηκε με επιτυχία για tenant #{$tenant->id}.");
            } catch (\Exception $e) {
                $this->error("Σφάλμα για tenant #{$tenant->id} ({$tenant->name}): " . $e->getMessage());
                if (isset($tenant) && $tenant->exists) {
                    $tenant->forgetCurrent();
                }
            }
        }

        $this->info("Η διαδικασία ολοκληρώθηκε.");
    }
}
