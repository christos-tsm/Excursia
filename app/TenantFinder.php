<?php

namespace App;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder as BaseTenantFinder;
use App\Models\Domain;

class TenantFinder extends BaseTenantFinder {
    /**
     * Create a new class instance.
     */
    public function __construct() {
        //
    }

    public function findForRequest(Request $request): ?Tenant {
        $host = $request->getHost();
        $fullUrl = $request->getSchemeAndHttpHost();

        // Αν βρισκόμαστε στο localhost
        if (str_contains($host, 'localhost')) {
            // Έλεγχος για το subdomain από το URL path (για τοπική ανάπτυξη)
            $path = $request->path();
            $pathSegments = explode('/', $path);

            // Αν το πρώτο τμήμα του path είναι "tenant" και υπάρχει δεύτερο τμήμα
            if (count($pathSegments) >= 2 && $pathSegments[0] === 'tenant') {
                $subdomainName = $pathSegments[1];

                // Αναζητούμε το domain
                $domain = Domain::where('domain', $subdomainName)->first();

                if (! $domain) {
                    return null;
                }

                return $domain->tenant;
            }

            return null;
        }

        // Κανονική περίπτωση (για production)
        // Αναζητούμε το domain
        $domain = Domain::where('domain', $host)->first();

        if (! $domain) {
            return null;
        }

        return $domain->tenant;
    }
}
