<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckTenantUsersCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:check-users {--tenant= : ID του συγκεκριμένου tenant για έλεγχο}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ελέγχει αν οι owners έχουν αντιγραφεί σωστά στις βάσεις των tenants';

    /**
     * Execute the console command.
     */
    public function handle() {
        // Έλεγχος αν έχει καθοριστεί συγκεκριμένο tenant ID
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                $this->error("Δεν βρέθηκε tenant με ID: {$tenantId}");
                return 1;
            }
            $tenants = collect([$tenant]);
        } else {
            // Παίρνουμε όλους τους ενεργούς tenants
            $tenants = Tenant::where('is_active', true)->get();
        }

        if ($tenants->isEmpty()) {
            $this->warn('Δεν βρέθηκαν ενεργοί tenants.');
            return 1;
        }

        $this->info("Βρέθηκαν {$tenants->count()} tenants για έλεγχο.");

        $this->table(
            ['ID', 'Όνομα Tenant', 'Βάση Δεδομένων', 'Owner ID', 'Owner Email', 'Αντιγράφηκε στη Βάση', 'Ρόλος Owner'],
            $this->getTenantUsersData($tenants)
        );

        return 0;
    }

    /**
     * Συλλογή δεδομένων για τους χρήστες των tenants
     */
    private function getTenantUsersData($tenants) {
        $data = [];

        foreach ($tenants as $tenant) {
            $row = [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'database' => $tenant->database,
                'owner_id' => $tenant->owner_id,
                'owner_email' => $tenant->owner ? $tenant->owner->email : 'Δεν βρέθηκε owner',
                'synced' => 'Ελέγχεται...',
                'has_role' => 'Ελέγχεται...'
            ];

            try {
                // Έλεγχος αν υπάρχει η βάση δεδομένων
                $databaseExists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$tenant->database}'");

                if (empty($databaseExists)) {
                    $row['synced'] = 'Δεν υπάρχει η βάση';
                    $row['has_role'] = 'N/A';
                    $data[] = $row;
                    continue;
                }

                // Χειροκίνητη σύνδεση στη βάση του tenant                $dbName = $tenant->database;                config(['database.connections.manual_tenant' => [                    'driver' => 'mysql',                    'host' => config('database.connections.mysql.host'),                    'port' => config('database.connections.mysql.port'),                    'database' => $dbName,                    'username' => config('database.connections.mysql.username'),                    'password' => config('database.connections.mysql.password'),                    'charset' => 'utf8mb4',                    'collation' => 'utf8mb4_unicode_ci',                    'prefix' => '',                ]]);                                // Καθαρισμός caching                DB::purge('manual_tenant');                                // Έλεγχος αν ο πίνακας users υπάρχει                $usersTableExists = DB::connection('manual_tenant')->select("SHOW TABLES LIKE 'users'");                if (empty($usersTableExists)) {                    $row['synced'] = 'Δεν υπάρχει ο πίνακας users';                    $row['has_role'] = 'N/A';                    $data[] = $row;                    continue;                }                                // Έλεγχος αν ο πίνακας roles υπάρχει                $rolesTableExists = DB::connection('manual_tenant')->select("SHOW TABLES LIKE 'roles'");                if (empty($rolesTableExists)) {                    $row['synced'] = 'Δεν υπάρχει ο πίνακας roles';                    $row['has_role'] = 'N/A';                    $data[] = $row;                    continue;                }                                // Έλεγχος αν ο χρήστης με ID 1 υπάρχει                $user = DB::connection('manual_tenant')->table('users')->where('id', 1)->first();                $row['synced'] = $user ? '✓' : '✗';                                // Έλεγχος αν έχει τον ρόλο owner                if ($user) {                    $ownerRole = DB::connection('manual_tenant')->table('roles')->where('name', 'owner')->first();                    if ($ownerRole) {                        $hasRole = DB::connection('manual_tenant')->table('model_has_roles')                            ->where('role_id', $ownerRole->id)                            ->where('model_type', 'App\\Models\\User')                            ->where('model_id', 1)                            ->first();                        $row['has_role'] = $hasRole ? '✓' : '✗';                    } else {                        $row['has_role'] = 'Δεν υπάρχει ο ρόλος owner';                    }                } else {                    $row['has_role'] = 'N/A';                }
            } catch (\Exception $e) {
                $row['synced'] = 'Σφάλμα: ' . $e->getMessage();
                $row['has_role'] = 'N/A';

                if (isset($tenant) && $tenant->exists) {
                    $tenant->forgetCurrent();
                }
            }

            $data[] = $row;
        }

        return $data;
    }
}
