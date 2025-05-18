<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantAddDomainsTableCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:add-domains-table {--tenant= : ID του συγκεκριμένου tenant για ενημέρωση}
                            {--force : Αναγκαστικός συγχρονισμός ακόμα κι αν ο πίνακας υπάρχει ήδη}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Προσθέτει ή ενημερώνει τον πίνακα domains σε όλους τους tenants';

    /**
     * Execute the console command.
     */
    public function handle() {
        // Έλεγχος αν έχει καθοριστεί συγκεκριμένο tenant ID
        $tenantId = $this->option('tenant');
        $force = $this->option('force');

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

        $this->info("Βρέθηκαν {$tenants->count()} tenants για ενημέρωση.");

        foreach ($tenants as $tenant) {
            $this->info("Επεξεργασία του tenant: {$tenant->name} (ID: {$tenant->id})");

            try {
                // Έλεγχος αν υπάρχει η βάση δεδομένων
                $databaseExists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$tenant->database}'");

                if (empty($databaseExists)) {
                    $this->warn("Η βάση δεδομένων '{$tenant->database}' δεν υπάρχει. Παράλειψη...");
                    continue;
                }

                // Χειροκίνητη σύνδεση στη βάση του tenant
                $dbName = $tenant->database;
                config(['database.connections.manual_tenant' => [
                    'driver' => 'mysql',
                    'host' => config('database.connections.mysql.host'),
                    'port' => config('database.connections.mysql.port'),
                    'database' => $dbName,
                    'username' => config('database.connections.mysql.username'),
                    'password' => config('database.connections.mysql.password'),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                ]]);

                // Καθαρισμός caching
                DB::purge('manual_tenant');

                $tableExists = Schema::connection('manual_tenant')->hasTable('domains');

                // Δημιουργία του πίνακα domains αν δεν υπάρχει
                if (!$tableExists) {
                    $this->info("Δημιουργία του πίνακα 'domains' στη βάση του tenant.");

                    Schema::connection('manual_tenant')->create('domains', function ($table) {
                        $table->id();
                        $table->string('domain')->unique();
                        $table->boolean('is_primary')->default(false);
                        $table->timestamps();
                    });
                } else {
                    $this->info("Ο πίνακας 'domains' υπάρχει ήδη στη βάση του tenant.");

                    // Αν έχει καθοριστεί η παράμετρος force, καθαρίζουμε τον πίνακα
                    if ($force) {
                        $this->info("Καθαρισμός του υπάρχοντος πίνακα domains...");
                        DB::connection('manual_tenant')->table('domains')->truncate();
                    }
                }

                // Αντιγραφή των domains από την κύρια βάση
                $domains = DB::table('domains')->where('tenant_id', $tenant->id)->get();

                if ($domains->isEmpty()) {
                    $this->warn("Δεν βρέθηκαν domains για το tenant ID: {$tenant->id} στην κύρια βάση.");
                    continue;
                }

                // Αντιγράφουμε τα domains μόνο αν ο πίνακας είναι άδειος ή έχει καθοριστεί η παράμετρος force
                $domainsCount = DB::connection('manual_tenant')->table('domains')->count();

                if ($domainsCount == 0 || $force) {
                    foreach ($domains as $domain) {
                        DB::connection('manual_tenant')->table('domains')->updateOrInsert(
                            ['domain' => $domain->domain],
                            [
                                'domain' => $domain->domain,
                                'is_primary' => $domain->is_primary,
                                'created_at' => $domain->created_at,
                                'updated_at' => $domain->updated_at,
                            ]
                        );
                    }

                    $this->info("Αντιγράφηκαν {$domains->count()} domains στη βάση του tenant.");
                } else {
                    $this->warn("Ο πίνακας 'domains' περιέχει ήδη δεδομένα. Χρησιμοποιήστε την παράμετρο --force για συγχρονισμό.");
                }
            } catch (\Exception $e) {
                $this->error("Σφάλμα για tenant #{$tenant->id}: " . $e->getMessage());
                continue;
            }
        }

        $this->info('Η διαδικασία ολοκληρώθηκε με επιτυχία.');

        return 0;
    }
}
