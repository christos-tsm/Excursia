<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Exceptions\NoCurrentTenant;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromPath {
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response {
        // Παίρνουμε το domain από το path
        $domainName = $request->route('domain');

        if (! $domainName) {
            return $next($request);
        }

        // Αναζητούμε το domain στη βάση
        $domain = Domain::where('domain', $domainName)->first();

        if (! $domain) {
            abort(404, 'Η επιχείρηση δεν βρέθηκε.');
        }

        // Παίρνουμε το tenant
        $tenant = $domain->tenant;

        if (! $tenant) {
            abort(404, 'Η επιχείρηση δεν βρέθηκε.');
        }

        // Έλεγχος αν το tenant είναι ενεργό
        if (! $tenant->is_active) {
            abort(403, 'Η επιχείρηση δεν είναι ενεργή.');
        }

        // Συγχρονισμός domains στη βάση του tenant αν τρέχουμε σε localhost
        if (request()->getHost() === 'localhost') {
            try {
                // Χειροκίνητη σύνδεση στη βάση του tenant
                $dbName = $tenant->database;
                config(['database.connections.tenant_check' => [
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
                DB::purge('tenant_check');

                // Έλεγχος αν η βάση του tenant έχει domains
                $domainsCount = DB::connection('tenant_check')->table('domains')->where('domain', $domainName)->count();

                if ($domainsCount === 0) {
                    Log::info("Συγχρονισμός domains για tenant {$tenant->name} επειδή λείπει το domain {$domainName} από τη βάση του");
                    Artisan::call('tenants:add-domains-table', [
                        '--tenant' => $tenant->id,
                        '--force' => true
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Σφάλμα ελέγχου domains: " . $e->getMessage());
            }
        }

        // Ορίζουμε το tenant ως current
        $tenant->makeCurrent();

        return $next($request);
    }
}
