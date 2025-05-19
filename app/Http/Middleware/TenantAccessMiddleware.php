<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class TenantAccessMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        // Παίρνουμε το tenant_id από το URL
        $tenant_id = $request->route('tenant_id');

        // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Οι super-admin και admin έχουν πρόσβαση σε όλα τα tenants
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return $next($request);
        }

        // Έλεγχος αν ο χρήστης ανήκει στο συγκεκριμένο tenant
        if ($user->tenant_id != $tenant_id) {
            // Αν ο χρήστης έχει tenant_id, τον ανακατευθύνουμε στο dashboard του tenant του
            if ($user->tenant_id) {
                return redirect()->route('tenant.dashboard', ['tenant_id' => $user->tenant_id])
                    ->with('error', 'Δεν έχετε πρόσβαση σε αυτό το tenant. Ανακατεύθυνση στο tenant σας.');
            }

            // Αν ο χρήστης δεν έχει tenant_id, τον ανακατευθύνουμε στην αρχική σελίδα
            return redirect()->route('welcome')
                ->with('error', 'Δεν έχετε πρόσβαση σε αυτό το tenant.');
        }

        // Έλεγχος αν το tenant είναι ενεργό
        $tenant = Tenant::find($tenant_id);
        if (!$tenant || !$tenant->is_active) {
            return redirect()->route('welcome')
                ->with('error', 'Το tenant δεν είναι ενεργό.');
        }

        return $next($request);
    }
}
