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

        // Αναζητούμε το domain
        $domain = Domain::where('domain', $host)->first();

        if (! $domain) {
            return null;
        }

        return $domain->tenant;
    }
}
