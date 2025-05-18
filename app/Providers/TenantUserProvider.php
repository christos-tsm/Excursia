<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TenantUserProvider extends EloquentUserProvider {
    /**
     * Επαληθεύει τα διαπιστευτήρια ενός χρήστη.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials) {
        $plain = $credentials['password'];

        return Hash::check($plain, $user->getAuthPassword());
    }

    /**
     * Ανακτά έναν χρήστη με βάση τα διαπιστευτήρια του.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials) {
        if (empty($credentials) || (count($credentials) === 1 && array_key_exists('password', $credentials))) {
            return null;
        }

        // Πρώτα, ελέγχουμε αν είμαστε σε domain tenant ή στο κεντρικό domain
        $host = request()->getHost();

        Log::info("Προσπάθεια αυθεντικοποίησης σε host: {$host}");

        // Ειδική περίπτωση για localhost - έλεγχος για domain parameter στο URL
        $domainParam = request()->get('domain');
        if ($host === 'localhost' && $domainParam) {
            Log::info("Localhost με domain parameter: {$domainParam}");
            $domain = DB::table('domains')->where('domain', $domainParam)->first();

            if ($domain) {
                $tenant = Tenant::whereHas('domains', function ($query) use ($domainParam) {
                    $query->where('domain', $domainParam);
                })->first();

                if (!$tenant || !$tenant->is_active) {
                    Log::warning("Απόπειρα σύνδεσης σε μη ενεργό tenant: {$domainParam}");
                    return null;
                }

                // Αναζήτηση χρήστη στη βάση του tenant
                return $this->findUserInTenantDatabase($tenant, $credentials);
            }
        }

        // Κανονική περίπτωση - έλεγχος για domain στον πίνακα domains
        $domain = DB::table('domains')->where('domain', $host)->first();

        if (!$domain) {
            // Είμαστε στο κεντρικό domain, ψάχνουμε στην κύρια βάση δεδομένων
            Log::info("Κεντρικό domain, αναζήτηση στην κύρια βάση");
            return parent::retrieveByCredentials($credentials);
        }

        // Βρίσκουμε τον tenant με βάση το domain
        $tenant = Tenant::whereHas('domains', function ($query) use ($host) {
            $query->where('domain', $host);
        })->first();

        if (!$tenant || !$tenant->is_active) {
            Log::warning("Απόπειρα σύνδεσης σε μη ενεργό tenant ή άγνωστο domain: {$host}");
            return null;
        }

        return $this->findUserInTenantDatabase($tenant, $credentials);
    }

    /**
     * Αναζητά έναν χρήστη στη βάση δεδομένων ενός tenant.
     *
     * @param  \App\Models\Tenant  $tenant
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function findUserInTenantDatabase($tenant, $credentials) {
        Log::info("Αυθεντικοποίηση στη βάση του tenant: {$tenant->name}");

        try {
            // Χειροκίνητη σύνδεση στη βάση του tenant
            $dbName = $tenant->database;
            config(['database.connections.tenant_auth' => [
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
            DB::purge('tenant_auth');

            // Δημιουργούμε το query βασισμένο στα credentials
            $query = "SELECT * FROM users WHERE ";
            $params = [];

            foreach ($credentials as $key => $value) {
                if ($key !== 'password') {
                    $query .= "{$key} = ? AND ";
                    $params[] = $value;
                }
            }

            // Αφαιρούμε το τελευταίο "AND"
            $query = rtrim($query, ' AND ');
            $query .= " LIMIT 1";

            // Εκτέλεση του query
            $result = DB::connection('tenant_auth')->select($query, $params);

            if (count($result) > 0) {
                // Βρέθηκε χρήστης στη βάση του tenant
                $userData = (array) $result[0];
                $user = new User($userData);
                $user->exists = true;

                // Θέτουμε τις ιδιότητες που δεν καλύπτονται από τον constructor
                foreach ($userData as $key => $value) {
                    $user->setAttribute($key, $value);
                }

                // Αποθηκεύουμε στη συνεδρία το tenant_id και το domain
                session(['current_tenant_id' => $tenant->id]);
                session(['current_tenant_domain' => request()->get('domain') ?: request()->getHost()]);

                Log::info("Επιτυχής αυθεντικοποίηση χρήστη από βάση tenant: {$credentials['email']}");

                return $user;
            }

            // Αν δεν βρεθεί στη βάση του tenant, κάνουμε έλεγχο και στην κύρια βάση
            // καθώς μπορεί να είναι admin ή owner του tenant που προσπαθεί να συνδεθεί
            // στο subdomain
            Log::info("Δεν βρέθηκε χρήστης στη βάση του tenant, έλεγχος στην κύρια βάση");
            return parent::retrieveByCredentials($credentials);
        } catch (\Exception $e) {
            Log::error('Σφάλμα κατά την αυθεντικοποίηση χρήστη tenant: ' . $e->getMessage());
            return null;
        }
    }
}
