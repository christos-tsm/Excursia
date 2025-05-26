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
            if (!$tenant || !$tenant->is_active) {
                // Κάνουμε logout τον χρήστη και τον ανακατευθύνουμε στο login με μήνυμα
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('status', 'Ο λογαριασμός σας δεν έχει εγκριθεί ακόμα. Παρακαλώ περιμένετε την έγκριση από τον διαχειριστή.');
            }
        }

        return $next($request);
    }
}
