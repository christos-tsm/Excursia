<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthenticatedSessionController extends Controller {
    /**
     * Display the login view.
     */
    public function create(): Response {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse {
        $request->authenticate();

        $request->session()->regenerate();

        // Ανακατεύθυνση με βάση τον ρόλο του χρήστη
        $user = Auth::user();

        // Έλεγχος αν είμαστε σε domain tenant
        $host = request()->getHost();

        // Ελέγχουμε για domain parameter στην περίπτωση του localhost
        $domainParam = $request->get('domain');
        if ($host === 'localhost' && $domainParam) {
            Log::info("Localhost με domain parameter: {$domainParam}");
            // Αν έχουμε domain στο URL, θεωρούμε ότι είμαστε σε tenant
            $domain = DB::table('domains')->where('domain', $domainParam)->first();

            if ($domain) {
                // Είμαστε ήδη σε domain tenant, ανακατεύθυνση στο dashboard του tenant
                Log::info("Ανακατεύθυνση στο dashboard tenant για domain: {$domainParam}");
                return redirect()->intended(route('tenant.dashboard', ['domain' => $domainParam], false));
            }
        }

        // Κανονική περίπτωση - έλεγχος για domain στον πίνακα domains
        $domain = DB::table('domains')->where('domain', $host)->first();

        if ($domain) {
            // Είμαστε ήδη σε domain tenant, ανακατεύθυνση στο dashboard του tenant
            return redirect()->intended(route('tenant.dashboard', absolute: false));
        }

        // Διαφορετικά, ελέγχουμε τον ρόλο του χρήστη για ανακατεύθυνση
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        } elseif ($user->tenant_id) {
            // Έλεγχος αν ο tenant είναι ενεργός
            $tenant = $user->tenant;
            if (!$tenant || !$tenant->is_active) {
                Log::info('Το tenant δεν είναι ενεργό ή δεν βρέθηκε');
                return redirect()->route('admin.tenants.pending');
            }

            // Βρίσκουμε το primary domain του tenant
            $domain = $tenant->domains()->where('is_primary', true)->first();

            if (!$domain) {
                Log::info('Δεν βρέθηκε domain για το tenant με ID: ' . $tenant->id);
                // Προσπαθούμε να βρούμε οποιοδήποτε domain
                $domain = $tenant->domains()->first();

                if (!$domain) {
                    Log::info('Ανακατεύθυνση στο κεντρικό dashboard (δεν βρέθηκε κανένα domain για το tenant)');
                    return redirect('/')->with('error', 'Δεν βρέθηκε domain για το tenant.');
                }
            }

            // Ανακατεύθυνση στο tenant dashboard με το σωστό domain
            if ($host === 'localhost') {
                // Για localhost, χρησιμοποιούμε παράμετρο domain
                Log::info("Ανακατεύθυνση σε localhost με domain παράμετρο: {$domain->domain}");
                return redirect()->route('tenant.dashboard', ['domain' => $domain->domain]);
            } else {
                // Κανονική περίπτωση - ανακατεύθυνση σε πλήρες URL
                $scheme = request()->secure() ? 'https://' : 'http://';
                $tenantUrl = $scheme . $domain->domain . '/tenant/dashboard';
                Log::info('Ανακατεύθυνση στο: ' . $tenantUrl);

                return redirect()->away($tenantUrl);
            }
        } else {
            // Ανακατεύθυνση όλων των άλλων χρηστών στην αρχική σελίδα
            return redirect()->intended('/');
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
