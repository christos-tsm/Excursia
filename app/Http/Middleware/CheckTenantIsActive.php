<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantIsActive {
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        $user = Auth::user();

        // Επιτρέπουμε πάντα την πρόσβαση σε super-admin και admin
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return $next($request);
        }

        // Ελέγχουμε αν ο χρήστης ανήκει σε tenant και αν αυτός είναι ενεργός
        if ($user->tenant_id) {
            $tenant = $user->tenant;
            if (!$tenant->is_active) {
                return redirect()->route('admin.tenants.pending');
            }
        }

        return $next($request);
    }
}
