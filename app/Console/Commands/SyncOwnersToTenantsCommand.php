<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncOwnersToTenantsCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:sync-owners';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Συγχρονίζει τους owners των tenants με τις αντίστοιχες βάσεις δεδομένων';

    /**
     * Execute the console command.
     */
    public function handle() {
        $this->info('Αναζήτηση ενεργών tenants...');

        // Παίρνουμε όλους τους ενεργούς tenants
        $tenants = Tenant::where('is_active', true)->get();

        if ($tenants->isEmpty()) {
            $this->warn('Δεν βρέθηκαν ενεργοί tenants.');
            return;
        }

        $this->info("Βρέθηκαν {$tenants->count()} ενεργοί tenants.");

        // Για κάθε tenant
        foreach ($tenants as $tenant) {
            $this->info("Συγχρονισμός owner για tenant #{$tenant->id} ({$tenant->name})...");

            try {
                // Έλεγχος αν υπάρχει η βάση δεδομένων
                $databaseExists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$tenant->database}'");

                if (empty($databaseExists)) {
                    $this->warn("Παράλειψη tenant #{$tenant->id} ({$tenant->name}): Η βάση δεδομένων '{$tenant->database}' δεν υπάρχει.");
                    continue;
                }

                // Κάνουμε τον tenant τρέχοντα
                $tenant->makeCurrent();

                // Έλεγχος αν ο πίνακας users υπάρχει
                $usersTableExists = DB::select("SHOW TABLES LIKE 'users'");
                if (empty($usersTableExists)) {
                    $this->warn("Παράλειψη tenant #{$tenant->id} ({$tenant->name}): Ο πίνακας 'users' δεν υπάρχει.");
                    $tenant->forgetCurrent();
                    continue;
                }

                // Έλεγχος αν ο πίνακας roles υπάρχει
                $rolesTableExists = DB::select("SHOW TABLES LIKE 'roles'");
                if (empty($rolesTableExists)) {
                    $this->warn("Παράλειψη tenant #{$tenant->id} ({$tenant->name}): Ο πίνακας 'roles' δεν υπάρχει.");
                    $tenant->forgetCurrent();
                    continue;
                }

                $owner = $tenant->owner;
                if (!$owner) {
                    $this->warn("Παράλειψη tenant #{$tenant->id} ({$tenant->name}): Δεν βρέθηκε owner.");
                    $tenant->forgetCurrent();
                    continue;
                }

                // Έλεγχος αν ο χρήστης υπάρχει ήδη
                $existingUser = DB::table('users')->where('id', 1)->first();

                if (!$existingUser) {
                    // Δημιουργία του χρήστη στη βάση του tenant
                    DB::table('users')->insert([
                        'id' => 1, // Δίνουμε ID 1 για να εξασφαλίσουμε αντιστοιχία
                        'name' => $owner->name,
                        'email' => $owner->email,
                        'password' => $owner->password,
                        'email_verified_at' => $owner->email_verified_at,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->info("Δημιουργήθηκε ο χρήστης στη βάση του tenant #{$tenant->id}.");
                } else {
                    $this->info("Ο χρήστης υπάρχει ήδη στη βάση του tenant #{$tenant->id}.");
                }

                // Έλεγχος αν υπάρχει ο ρόλος owner
                $ownerRole = DB::table('roles')->where('name', 'owner')->first();
                if (!$ownerRole) {
                    $this->warn("Παράλειψη tenant #{$tenant->id} ({$tenant->name}): Δεν βρέθηκε ο ρόλος 'owner'.");
                    $tenant->forgetCurrent();
                    continue;
                }

                // Έλεγχος αν η ανάθεση ρόλου υπάρχει ήδη
                $existingRole = DB::table('model_has_roles')
                    ->where('role_id', $ownerRole->id)
                    ->where('model_type', 'App\\Models\\User')
                    ->where('model_id', 1)
                    ->first();

                if (!$existingRole) {
                    DB::table('model_has_roles')->insert([
                        'role_id' => $ownerRole->id,
                        'model_type' => 'App\\Models\\User',
                        'model_id' => 1,
                    ]);
                    $this->info("Ανατέθηκε ο ρόλος 'owner' στο χρήστη στη βάση του tenant #{$tenant->id}.");
                } else {
                    $this->info("Ο ρόλος 'owner' έχει ήδη ανατεθεί στο χρήστη στη βάση του tenant #{$tenant->id}.");
                }

                // Αποδεσμεύουμε τον tenant
                $tenant->forgetCurrent();
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
    }
}
