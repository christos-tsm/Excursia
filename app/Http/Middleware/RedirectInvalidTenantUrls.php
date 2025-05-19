<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RedirectInvalidTenantUrls {
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response {
        $path = $request->path();

        // Έλεγχος αν το URL ξεκινάει με tenant/
        if (str_starts_with($path, 'tenant/')) {
            $pathSegments = explode('/', $path);

            // Αν υπάρχει μόνο ένα τμήμα (tenant/) χωρίς tenant_id
            if (count($pathSegments) < 2) {
                // Αν ο χρήστης είναι συνδεδεμένος και έχει tenant_id
                if (Auth::check() && Auth::user()->tenant_id) {
                    // Ανακατευθύνουμε στο dashboard του tenant του
                    return redirect()->route('tenant.dashboard', ['tenant_id' => Auth::user()->tenant_id]);
                }

                // Αν ο χρήστης δεν έχει tenant_id, τον στέλνουμε στην αρχική
                return redirect()->route('welcome')
                    ->with('error', 'Παρακαλώ χρησιμοποιήστε το σωστό URL για την επιχείρησή σας: /tenant/{tenant_id}/...');
            }

            // Αν το δεύτερο τμήμα του path δεν είναι αριθμός (tenant_id)
            if (!is_numeric($pathSegments[1])) {
                // Αν ο χρήστης είναι συνδεδεμένος και έχει tenant_id
                if (Auth::check() && Auth::user()->tenant_id) {
                    // Ανακατευθύνουμε στο αντίστοιχο URL με το tenant_id του
                    $redirectPath = 'tenant/' . Auth::user()->tenant_id;
                    if (count($pathSegments) > 1) {
                        $redirectPath .= '/' . implode('/', array_slice($pathSegments, 1));
                    } else {
                        $redirectPath .= '/dashboard';
                    }
                    return redirect($redirectPath);
                }

                // Αλλιώς, τον στέλνουμε στην αρχική
                return redirect()->route('welcome')
                    ->with('error', 'Παρακαλώ χρησιμοποιήστε το σωστό URL για την επιχείρησή σας: /tenant/{tenant_id}/...');
            }
        }

        return $next($request);
    }
}
