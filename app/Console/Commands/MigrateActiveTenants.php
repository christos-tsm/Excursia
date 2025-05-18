<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class MigrateActiveTenants extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:migrate-active {--path= : Το path για τα migrations, π.χ. database/migrations/tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Εκτελεί migrations μόνο για τους ενεργούς tenants των οποίων οι βάσεις υπάρχουν';

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

        $path = $this->option('path') ?: 'database/migrations/tenant';

        // Για κάθε tenant
        foreach ($tenants as $tenant) {
            $database = $tenant->database;

            // Έλεγχος αν υπάρχει η βάση δεδομένων
            try {
                // Δοκιμάζουμε να συνδεθούμε στη βάση
                $hasDatabase = $this->databaseExists($database);

                if (!$hasDatabase) {
                    $this->warn("Παράλειψη tenant #{$tenant->id} ({$tenant->name}): Η βάση δεδομένων '{$database}' δεν υπάρχει.");
                    continue;
                }

                // Εκτέλεση των migrations
                $this->info("Εκτέλεση migrations για tenant #{$tenant->id} ({$tenant->name}) στη βάση '{$database}'...");

                // Κάνουμε τον tenant τρέχοντα
                $tenant->makeCurrent();

                // Εκτελούμε το migration
                $this->comment("php artisan migrate --path={$path} --database=tenant");

                $exitCode = Artisan::call('migrate', [
                    '--path' => $path,
                    '--database' => 'tenant',
                ]);

                if ($exitCode === 0) {
                    $this->info("Επιτυχής εκτέλεση migrations για tenant #{$tenant->id}.");
                } else {
                    $this->error("Αποτυχία εκτέλεσης migrations για tenant #{$tenant->id}.");
                }

                // Αποδεσμεύουμε τον tenant
                $tenant->forgetCurrent();
            } catch (\Exception $e) {
                $this->error("Σφάλμα για tenant #{$tenant->id} ({$tenant->name}): " . $e->getMessage());
                continue;
            }
        }

        $this->info('Η διαδικασία ολοκληρώθηκε.');
    }

    /**
     * Έλεγχος αν υπάρχει η βάση δεδομένων
     */
    protected function databaseExists($database) {
        try {
            // Παίρνουμε τη σύνδεση tenant όπως είναι ρυθμισμένη
            $connection = DB::connection('tenant')->getConfig();

            // Δημιουργούμε μια νέα σύνδεση για να ελέγξουμε αν υπάρχει η βάση
            $tempConnection = array_merge($connection, ['database' => 'information_schema']);

            // Προσωρινή σύνδεση στο information_schema
            $pdo = new \PDO(
                "mysql:host={$tempConnection['host']};dbname=information_schema",
                $tempConnection['username'],
                $tempConnection['password']
            );

            // Έλεγχος αν υπάρχει η βάση
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$database]);

            return $stmt->fetchColumn() !== false;
        } catch (\Exception $e) {
            $this->error("Σφάλμα κατά τον έλεγχο της βάσης '{$database}': " . $e->getMessage());
            return false;
        }
    }
}
