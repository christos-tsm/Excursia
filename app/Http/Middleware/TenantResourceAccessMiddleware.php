<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantResourceAccessMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        $tenant_id = $request->route('tenant_id');
        $user = Auth::user();

        // Σε αυτό το σημείο θα εξετάσουμε αν οι παράμετροι του route ανήκουν στο tenant
        // Εξετάζουμε όλα τα model bindings του route
        foreach ($request->route()->parameters() as $key => $value) {
            // Αν είναι model binding και έχει tenant_id property
            if (is_object($value) && property_exists($value, 'tenant_id')) {
                // Αν το tenant_id του μοντέλου δεν ταιριάζει με το tenant_id του URL
                if ($value->tenant_id != $tenant_id) {
                    // Αν είναι admin, μπορεί να δει πόρους από άλλα tenants
                    if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
                        abort(404, 'Ο πόρος αυτός δεν είναι διαθέσιμος.');
                    }
                }
            }
        }

        return $next($request);
    }
}
