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

        // Διαφορετικά, ελέγχουμε τον ρόλο του χρήστη για ανακατεύθυνση
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return redirect()->intended(route('admin.dashboard'));
        } elseif ($user->tenant_id) {
            // Έλεγχος αν ο tenant είναι ενεργός
            $tenant = $user->tenant;
            if (!$tenant || !$tenant->is_active) {
                Log::info('Το tenant δεν είναι ενεργό ή δεν βρέθηκε');
                return redirect()->route('admin.tenants.pending');
            }

            // Ανακατεύθυνση στο tenant dashboard
            return redirect()->intended(route('tenant.dashboard', ['tenant_id' => $tenant->id]));
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
