<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant;
use App\Models\Domain;

class RedirectInvalidTenantUrls {
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response {
        $path = $request->path();

        // Έλεγχος αν το URL ξεκινάει με tenant/
        if (str_starts_with($path, 'tenant/')) {
            $pathSegments = explode('/', $path);

            // Αν υπάρχει μόνο ένα τμήμα ή το δεύτερο τμήμα είναι ένα γνωστό endpoint (όχι domain)
            if (count($pathSegments) < 2 || in_array($pathSegments[1], ['dashboard', 'trips', 'invitations'])) {
                // Αν ο χρήστης είναι συνδεδεμένος, προσπαθούμε να βρούμε το tenant του
                if (Auth::check() && Auth::user()->tenant_id) {
                    $tenant = Tenant::find(Auth::user()->tenant_id);
                    if ($tenant) {
                        $domain = Domain::where('tenant_id', $tenant->id)->where('is_primary', true)->first();
                        if ($domain) {
                            // Ανακατευθύνουμε στο σωστό URL
                            $redirectPath = 'tenant/' . $domain->domain;
                            if (count($pathSegments) > 1) {
                                $redirectPath .= '/' . implode('/', array_slice($pathSegments, 1));
                            } else {
                                $redirectPath .= '/dashboard';
                            }
                            return redirect($redirectPath);
                        }
                    }
                }

                // Αν δεν μπορούμε να βρούμε το tenant του χρήστη, ανακατευθύνουμε στην αρχική σελίδα
                return redirect()->route('welcome')
                    ->with('error', 'Παρακαλώ χρησιμοποιήστε το σωστό URL για την επιχείρησή σας: /tenant/{domain}/...');
            }
        }

        return $next($request);
    }
}
