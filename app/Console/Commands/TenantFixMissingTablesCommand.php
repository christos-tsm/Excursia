<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class TenantFixMissingTablesCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:fix-tables {--tenant= : ID του συγκεκριμένου tenant για επισκευή}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Επισκευάζει τυχόν πίνακες που λείπουν σε βάσεις δεδομένων tenants και συγχρονίζει τους owners';

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

        $this->info("Βρέθηκαν {$tenants->count()} tenants για επισκευή.");

        // Για κάθε tenant
        foreach ($tenants as $tenant) {
            $this->info("Επισκευή tenant #{$tenant->id} ({$tenant->name})...");

            try {
                // Έλεγχος αν υπάρχει η βάση δεδομένων
                $databaseExists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$tenant->database}'");

                if (empty($databaseExists)) {
                    $this->warn("Παράλειψη tenant #{$tenant->id} ({$tenant->name}): Η βάση δεδομένων '{$tenant->database}' δεν υπάρχει.");
                    continue;
                }

                // Έλεγχος και εκτέλεση migrations
                $this->info("Εκτέλεση migration για τον πίνακα users...");

                // Χειροκίνητη σύνδεση στη βάση του tenant                $dbName = $tenant->database;                config(['database.connections.manual_tenant' => [                    'driver' => 'mysql',                    'host' => config('database.connections.mysql.host'),                    'port' => config('database.connections.mysql.port'),                    'database' => $dbName,                    'username' => config('database.connections.mysql.username'),                    'password' => config('database.connections.mysql.password'),                    'charset' => 'utf8mb4',                    'collation' => 'utf8mb4_unicode_ci',                    'prefix' => '',                ]]);                                // Καθαρισμός τυχόν cached connections                DB::purge('manual_tenant');                                // Έλεγχος αν ο πίνακας users υπάρχει                $usersTableExists = DB::connection('manual_tenant')->select("SHOW TABLES LIKE 'users'");                if (empty($usersTableExists)) {                    $this->info("Ο πίνακας 'users' λείπει, εκτέλεση migration...");                    // Εκτέλεση του migration για τον πίνακα users                    Artisan::call('tenants:artisan', [                        'artisanCommand' => "migrate --path=database/migrations/tenant/2014_10_12_000000_create_users_table.php --database=tenant",                        '--tenant' => $tenant->id                    ]);                }                // Έλεγχος αν ο πίνακας roles υπάρχει                $rolesTableExists = DB::connection('manual_tenant')->select("SHOW TABLES LIKE 'roles'");                if (empty($rolesTableExists)) {                    $this->info("Ο πίνακας 'roles' λείπει, εκτέλεση migration...");                    // Εκτέλεση του migration για τους πίνακες permissions                    Artisan::call('tenants:artisan', [                        'artisanCommand' => "migrate --path=database/migrations/tenant/2024_06_23_000002_create_permission_tables.php --database=tenant",                        '--tenant' => $tenant->id                    ]);                }                // Έλεγχος αν ο πίνακας invitations υπάρχει                $invitationsTableExists = DB::connection('manual_tenant')->select("SHOW TABLES LIKE 'invitations'");                if (empty($invitationsTableExists)) {                    $this->info("Ο πίνακας 'invitations' λείπει, εκτέλεση migration...");                    // Εκτέλεση του migration για τον πίνακα invitations                    Artisan::call('tenants:artisan', [                        'artisanCommand' => "migrate --path=database/migrations/tenant/2024_06_24_000002_create_invitations_table.php --database=tenant",                        '--tenant' => $tenant->id                    ]);                }                // Τώρα συγχρονίζουμε τον owner                $owner = $tenant->owner;                if (!$owner) {                    $this->warn("Παράλειψη tenant #{$tenant->id} ({$tenant->name}): Δεν βρέθηκε owner.");                    continue;                }                // Έλεγχος αν ο χρήστης υπάρχει ήδη                $existingUser = DB::connection('manual_tenant')->table('users')->where('id', 1)->first();                if (!$existingUser) {                    // Δημιουργία του χρήστη στη βάση του tenant                    DB::connection('manual_tenant')->table('users')->insert([                        'id' => 1, // Δίνουμε ID 1 για να εξασφαλίσουμε αντιστοιχία                        'name' => $owner->name,                        'email' => $owner->email,                        'password' => $owner->password,                        'email_verified_at' => $owner->email_verified_at,                        'created_at' => now(),                        'updated_at' => now(),                    ]);                    $this->info("Δημιουργήθηκε ο χρήστης στη βάση του tenant #{$tenant->id}.");                } else {                    $this->info("Ο χρήστης υπάρχει ήδη στη βάση του tenant #{$tenant->id}.");                }                // Έλεγχος αν υπάρχει ο ρόλος owner                $ownerRole = DB::connection('manual_tenant')->table('roles')->where('name', 'owner')->first();                if (!$ownerRole) {                    $this->warn("Παράλειψη ανάθεσης ρόλου για tenant #{$tenant->id}: Δεν βρέθηκε ο ρόλος 'owner'.");                } else {                    // Έλεγχος αν η ανάθεση ρόλου υπάρχει ήδη                    $existingRole = DB::connection('manual_tenant')->table('model_has_roles')                        ->where('role_id', $ownerRole->id)                        ->where('model_type', 'App\\Models\\User')                        ->where('model_id', 1)                        ->first();                    if (!$existingRole) {                        DB::connection('manual_tenant')->table('model_has_roles')->insert([                            'role_id' => $ownerRole->id,                            'model_type' => 'App\\Models\\User',                            'model_id' => 1,                        ]);                        $this->info("Ανατέθηκε ο ρόλος 'owner' στο χρήστη στη βάση του tenant #{$tenant->id}.");                    } else {                        $this->info("Ο ρόλος 'owner' έχει ήδη ανατεθεί στο χρήστη στη βάση του tenant #{$tenant->id}.");                    }                }
                $this->info("Επιτυχής συγχρονισμός για tenant #{$tenant->id}.");
            } catch (\Exception $e) {
                $this->error("Σφάλμα για tenant #{$tenant->id} ({$tenant->name}): " . $e->getMessage());

                if (isset($tenant) && $tenant->exists) {
                    $tenant->forgetCurrent();
                }

                continue;
            }
        }

        $this->info('Η διαδικασία ολοκληρώθηκε με επιτυχία.');
        return 0;
    }
}
