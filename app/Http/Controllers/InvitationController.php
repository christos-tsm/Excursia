<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Tenant;
use App\Mail\InvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class InvitationController extends Controller {
    /**
     * Εμφάνιση λίστας προσκλήσεων
     */
    public function index(Request $request) {
        $tenant_id = $request->route('tenant_id');
        $tenant = Tenant::findOrFail($tenant_id);

        $invitations = Invitation::with('inviter')
            ->where('tenant_id', $tenant_id)
            ->latest()
            ->paginate(10);

        return Inertia::render('Tenant/Invitations/Index', [
            'invitations' => $invitations,
            'tenant_id' => $tenant_id,
            'success' => session('message'),
            'error' => session('error')
        ]);
    }

    /**
     * Εμφάνιση φόρμας για δημιουργία νέας πρόσκλησης
     */
    public function create(Request $request) {
        $tenant_id = $request->route('tenant_id');

        return Inertia::render('Tenant/Invitations/Create', [
            'tenant_id' => $tenant_id
        ]);
    }

    /**
     * Αποθήκευση νέας πρόσκλησης
     */
    public function store(Request $request) {
        $tenant_id = $request->route('tenant_id');
        $tenant = Tenant::findOrFail($tenant_id);
        $user = Auth::user();

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(['guide', 'staff'])],
        ]);

        // Έλεγχος αν υπάρχει ήδη ενεργή πρόσκληση για αυτό το email
        $existingInvitation = Invitation::where('email', $validated['email'])
            ->where('tenant_id', $tenant_id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now()->format('Y-m-d H:i:s'))
            ->first();

        if ($existingInvitation) {
            return back()->with('error', 'Υπάρχει ήδη ενεργή πρόσκληση για αυτό το email.');
        }

        // Δημιουργία πρόσκλησης
        $invitation = Invitation::create([
            'tenant_id' => $tenant_id,
            'email' => $validated['email'],
            'name' => $validated['name'],
            'token' => Str::random(64),
            'role' => $validated['role'],
            'invited_by' => $user->id,
            'expires_at' => now()->addDays(7), // Η πρόσκληση λήγει σε 7 ημέρες
        ]);

        // Αποστολή email
        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        return redirect()->route('tenant.invitations.index', ['tenant_id' => $tenant_id])
            ->with('message', 'Η πρόσκληση στάλθηκε με επιτυχία.');
    }

    /**
     * Σελίδα αποδοχής πρόσκλησης
     */
    public function showAcceptForm(Request $request, $token) {
        // Αναζήτηση πρόσκλησης με το συγκεκριμένο token
        $invitation = Invitation::where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$invitation) {
            return redirect()->route('welcome')
                ->with('error', 'Η πρόσκληση δεν είναι έγκυρη ή έχει λήξει.');
        }

        // Έλεγχος αν το tenant είναι ενεργό
        $tenant = Tenant::find($invitation->tenant_id);
        if (!$tenant || !$tenant->is_active) {
            return redirect()->route('welcome')
                ->with('error', 'Η επιχείρηση δεν είναι πλέον ενεργή.');
        }

        return Inertia::render('Tenant/Invitations/Accept', [
            'invitation' => $invitation,
            'tenant' => $tenant,
            'email' => $invitation->email,
            'name' => $invitation->name,
            'error' => session('error'),
        ]);
    }

    /**
     * Αποδοχή πρόσκλησης και δημιουργία λογαριασμού
     */
    public function accept(Request $request, $token) {
        $invitation = Invitation::where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$invitation) {
            return redirect()->route('welcome')
                ->with('error', 'Η πρόσκληση δεν είναι έγκυρη ή έχει λήξει.');
        }

        // Έλεγχος αν το tenant είναι ενεργό
        $tenant = Tenant::find($invitation->tenant_id);
        if (!$tenant || !$tenant->is_active) {
            return redirect()->route('welcome')
                ->with('error', 'Η επιχείρηση δεν είναι πλέον ενεργή.');
        }

        // Επικύρωση των δεδομένων
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        // Έλεγχος αν υπάρχει ήδη χρήστης με αυτό το email
        $existingUser = User::where('email', $invitation->email)->first();
        if ($existingUser) {
            // Αν ο χρήστης υπάρχει, απλώς ενημερώνουμε το tenant_id
            $existingUser->tenant_id = $invitation->tenant_id;
            $existingUser->save();

            // Ανάθεση ρόλου στον χρήστη
            if ($invitation->role) {
                $existingUser->assignRole($invitation->role);
                Log::info("Ανατέθηκε ο ρόλος '{$invitation->role}' στον χρήστη με ID: {$existingUser->id}");
            }

            // Ενημέρωση της πρόσκλησης
            $invitation->accepted_at = now();
            $invitation->save();

            return redirect()->route('login')
                ->with('message', 'Η πρόσκληση έγινε αποδεκτή. Παρακαλώ συνδεθείτε με τα διαπιστευτήριά σας.');
        } else {
            // Δημιουργία νέου χρήστη
            $user = User::create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'password' => Hash::make($validated['password']),
                'tenant_id' => $invitation->tenant_id,
            ]);

            // Ανάθεση ρόλου στον χρήστη
            if ($invitation->role) {
                $user->assignRole($invitation->role);
                Log::info("Ανατέθηκε ο ρόλος '{$invitation->role}' στον χρήστη με ID: {$user->id}");
            }

            // Ενημέρωση της πρόσκλησης
            $invitation->accepted_at = now();
            $invitation->save();

            // Αυτόματη σύνδεση του χρήστη
            Auth::login($user);

            return redirect()->route('tenant.dashboard', ['tenant_id' => $invitation->tenant_id])
                ->with('message', 'Καλώς ήρθατε! Ο λογαριασμός σας δημιουργήθηκε επιτυχώς.');
        }
    }

    /**
     * Διαγραφή πρόσκλησης
     */
    public function destroy(Request $request, $invitation_id) {
        $tenant_id = $request->route('tenant_id');

        // Ανάκτηση της πρόσκλησης από το ID
        $invitation = Invitation::findOrFail($invitation_id);

        $invitation->delete();

        return redirect()->route('tenant.invitations.index', ['tenant_id' => $tenant_id])
            ->with('message', 'Η πρόσκληση διαγράφηκε επιτυχώς.');
    }

    /**
     * Αποστολή ξανά της πρόσκλησης
     */
    public function resend(Request $request, $invitation_id) {
        $tenant_id = $request->route('tenant_id');

        // Ανάκτηση της πρόσκλησης από το ID
        $invitation = Invitation::findOrFail($invitation_id);

        if ($invitation->accepted_at) {
            return back()->with('error', 'Η πρόσκληση έχει ήδη γίνει αποδεκτή.');
        }

        // Ανανέωση της ημερομηνίας λήξης
        $invitation->expires_at = now()->addDays(7);
        $invitation->save();

        // Αποστολή email
        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        return back()->with('message', 'Η πρόσκληση στάλθηκε ξανά επιτυχώς.');
    }
}
