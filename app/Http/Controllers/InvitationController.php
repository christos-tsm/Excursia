<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use App\Mail\InvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Permission\Models\Role;
use App\Models\Domain;

class InvitationController extends Controller {
    /**
     * Private μέθοδος για την εύρεση του tenant από το URL
     */
    private function findTenantFromRequest(Request $request) {
        // Προσπαθούμε να πάρουμε το domain από το URL αν είναι μορφής tenant/{domain}/...
        $path = $request->path();
        $pathSegments = explode('/', $path);

        // Αν το URL είναι της μορφής tenant/{domain}/...
        if (count($pathSegments) >= 3 && $pathSegments[0] === 'tenant') {
            $domainName = $pathSegments[1];
            $domain = Domain::where('domain', $domainName)->first();

            if ($domain && $domain->tenant) {
                $domain->tenant->makeCurrent();
            }
        }

        // Επιστρέφουμε τον τρέχοντα tenant
        return Tenant::current();
    }

    /**
     * Εμφάνιση λίστας προσκλήσεων
     */
    public function index(Request $request) {
        // Βρίσκουμε τον tenant
        $tenant = $this->findTenantFromRequest($request);

        if (!$tenant) {
            return back()->with('error', 'Δεν βρέθηκε ενεργός tenant. Παρακαλώ χρησιμοποιήστε το σωστό URL.');
        }

        $invitations = Invitation::with('inviter')
            ->latest()
            ->paginate(10);

        return Inertia::render('Tenant/Invitations/Index', [
            'invitations' => $invitations,
            'success' => session('message'),
            'error' => session('error')
        ]);
    }

    /**
     * Εμφάνιση φόρμας για δημιουργία νέας πρόσκλησης
     */
    public function create(Request $request) {
        // Βρίσκουμε τον tenant
        $tenant = $this->findTenantFromRequest($request);

        if (!$tenant) {
            return back()->with('error', 'Δεν βρέθηκε ενεργός tenant. Παρακαλώ χρησιμοποιήστε το σωστό URL.');
        }

        return Inertia::render('Tenant/Invitations/Create');
    }

    /**
     * Αποθήκευση νέας πρόσκλησης
     */
    public function store(Request $request) {
        // Βρίσκουμε τον tenant
        $tenant = $this->findTenantFromRequest($request);

        if (!$tenant) {
            return back()->with('error', 'Δεν βρέθηκε ενεργός tenant. Παρακαλώ χρησιμοποιήστε το σωστό URL.');
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(['guide', 'staff'])],
        ]);

        // Έλεγχος αν υπάρχει ήδη ενεργή πρόσκληση για αυτό το email
        $existingInvitation = Invitation::where('email', $validated['email'])
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now()->format('Y-m-d H:i:s'))
            ->first();

        if ($existingInvitation) {
            return back()->with('error', 'Υπάρχει ήδη ενεργή πρόσκληση για αυτό το email.');
        }

        // Αρχικά ελέγχουμε αν ο τρέχων χρήστης υπάρχει στη βάση του tenant
        $tenantUser = null;
        if ($tenant) {
            // Χειροκίνητη σύνδεση στη βάση του tenant
            $dbName = $tenant->database;

            config(['database.connections.manual_tenant' => [
                'driver' => 'mysql',
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port'),
                'database' => $dbName,
                'username' => config('database.connections.mysql.username'),
                'password' => config('database.connections.mysql.password'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ]]);

            // Καθαρισμός caching
            DB::purge('manual_tenant');

            // Ελέγχουμε αν υπάρχει ο χρήστης στον tenant
            $email = Auth::user()->email;
            try {
                $tenantUser = DB::connection('manual_tenant')->table('users')->where('email', $email)->first();
            } catch (\Exception $e) {
                Log::error("Σφάλμα κατά την αναζήτηση χρήστη στη βάση του tenant: " . $e->getMessage());
            }
        }

        // Δημιουργία πρόσκλησης
        $invitation = Invitation::create([
            'email' => $validated['email'],
            'name' => $validated['name'],
            'token' => Str::random(64),
            'role' => $validated['role'],
            'invited_by' => $tenantUser ? $tenantUser->id : 1, // Χρησιμοποιούμε το ID του tenant user ή 1 (owner) αν δεν βρέθηκε
            'expires_at' => now()->addDays(7), // Η πρόσκληση λήγει σε 7 ημέρες
        ]);

        // Αποστολή email
        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        // Βρίσκουμε το domain από το URL
        $path = $request->path();
        $pathSegments = explode('/', $path);
        $domainName = $pathSegments[1] ?? '';

        return redirect()->route('tenant.invitations.index', ['domain' => $domainName])
            ->with('message', 'Η πρόσκληση στάλθηκε με επιτυχία.');
    }

    /**
     * Σελίδα αποδοχής πρόσκλησης
     */
    public function showAcceptForm(Request $request, $token) {
        // Προσπαθούμε να βρούμε το domain από το URL
        $path = $request->path();
        $pathSegments = explode('/', $path);
        $domain = null;

        // Έλεγχος αν το URL περιέχει τη μορφή tenant/{domain}
        if (count($pathSegments) >= 2 && $pathSegments[0] === 'tenant') {
            $domainName = $pathSegments[1];
            $domain = Domain::where('domain', $domainName)->first();
        }

        $tenant = null;
        if ($domain && $domain->tenant) {
            $tenant = $domain->tenant;
        }

        // Αν δεν βρέθηκε tenant, θα πρέπει να ψάξουμε σε όλους τους tenants
        if (!$tenant) {
            // Παίρνουμε όλους τους ενεργούς tenants
            $tenants = Tenant::where('is_active', true)->get();

            foreach ($tenants as $currentTenant) {
                // Χειροκίνητη σύνδεση στη βάση του tenant
                $dbName = $currentTenant->database;

                try {
                    config(['database.connections.manual_tenant' => [
                        'driver' => 'mysql',
                        'host' => config('database.connections.mysql.host'),
                        'port' => config('database.connections.mysql.port'),
                        'database' => $dbName,
                        'username' => config('database.connections.mysql.username'),
                        'password' => config('database.connections.mysql.password'),
                        'charset' => 'utf8mb4',
                        'collation' => 'utf8mb4_unicode_ci',
                        'prefix' => '',
                    ]]);

                    // Καθαρισμός caching
                    DB::purge('manual_tenant');

                    // Ελέγχουμε αν υπάρχει πίνακας invitations
                    $hasInvitationsTable = DB::connection('manual_tenant')
                        ->select("SHOW TABLES LIKE 'invitations'");

                    if (!empty($hasInvitationsTable)) {
                        // Ελέγχουμε αν υπάρχει η πρόσκληση
                        $invitation = DB::connection('manual_tenant')
                            ->table('invitations')
                            ->where('token', $token)
                            ->first();

                        if ($invitation) {
                            $tenant = $currentTenant;
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Σφάλμα κατά την αναζήτηση της πρόσκλησης στον tenant {$currentTenant->name}: " . $e->getMessage());
                    continue;
                }
            }
        }

        if (!$tenant) {
            return abort(404, 'Η πρόσκληση δεν βρέθηκε.');
        }

        // Συνδεόμαστε στη βάση του tenant
        $dbName = $tenant->database;
        config(['database.connections.manual_tenant' => [
            'driver' => 'mysql',
            'host' => config('database.connections.mysql.host'),
            'port' => config('database.connections.mysql.port'),
            'database' => $dbName,
            'username' => config('database.connections.mysql.username'),
            'password' => config('database.connections.mysql.password'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]]);

        // Καθαρισμός caching
        DB::purge('manual_tenant');

        // Βρίσκουμε την πρόσκληση
        try {
            $invitationData = DB::connection('manual_tenant')
                ->table('invitations')
                ->where('token', $token)
                ->first();

            if (!$invitationData) {
                return abort(404, 'Η πρόσκληση δεν βρέθηκε.');
            }

            // Δημιουργούμε ένα μοντέλο για την εμφάνιση
            $invitation = new Invitation((array)$invitationData);

            // Έλεγχος αν η πρόσκληση έχει λήξει
            $isExpired = $invitation->expires_at && now()->gt($invitation->expires_at);
            if ($isExpired) {
                return Inertia::render('Tenant/Invitations/Accept', [
                    'invitation' => $invitationData,
                    'error' => 'Η πρόσκληση έχει λήξει.',
                    'domain' => $domain ? $domain->domain : null,
                ]);
            }

            // Έλεγχος αν η πρόσκληση έχει ήδη γίνει αποδεκτή
            $isAccepted = $invitation->accepted_at !== null;
            if ($isAccepted) {
                return Inertia::render('Tenant/Invitations/Accept', [
                    'invitation' => $invitationData,
                    'error' => 'Η πρόσκληση έχει ήδη γίνει αποδεκτή.',
                    'domain' => $domain ? $domain->domain : null,
                ]);
            }

            return Inertia::render('Tenant/Invitations/Accept', [
                'invitation' => $invitationData,
                'email' => $invitationData->email,
                'name' => $invitationData->name,
                'domain' => $domain ? $domain->domain : null,
            ]);
        } catch (\Exception $e) {
            Log::error("Σφάλμα κατά την αναζήτηση της πρόσκλησης: " . $e->getMessage());
            return abort(500, 'Προέκυψε σφάλμα κατά την αναζήτηση της πρόσκλησης.');
        }
    }

    /**
     * Επεξεργασία αποδοχής πρόσκλησης
     */
    public function accept(Request $request, $token) {
        // Προσπαθούμε να βρούμε το domain από το URL
        $path = $request->path();
        $pathSegments = explode('/', $path);
        $domain = null;

        // Έλεγχος αν το URL περιέχει τη μορφή tenant/{domain}
        if (count($pathSegments) >= 2 && $pathSegments[0] === 'tenant') {
            $domainName = $pathSegments[1];
            $domain = Domain::where('domain', $domainName)->first();
        }

        $tenant = null;
        if ($domain && $domain->tenant) {
            $tenant = $domain->tenant;
        }

        // Αν δεν βρέθηκε tenant, θα πρέπει να ψάξουμε σε όλους τους tenants
        if (!$tenant) {
            // Παίρνουμε όλους τους ενεργούς tenants
            $tenants = Tenant::where('is_active', true)->get();

            foreach ($tenants as $currentTenant) {
                // Χειροκίνητη σύνδεση στη βάση του tenant
                $dbName = $currentTenant->database;

                try {
                    config(['database.connections.manual_tenant' => [
                        'driver' => 'mysql',
                        'host' => config('database.connections.mysql.host'),
                        'port' => config('database.connections.mysql.port'),
                        'database' => $dbName,
                        'username' => config('database.connections.mysql.username'),
                        'password' => config('database.connections.mysql.password'),
                        'charset' => 'utf8mb4',
                        'collation' => 'utf8mb4_unicode_ci',
                        'prefix' => '',
                    ]]);

                    // Καθαρισμός caching
                    DB::purge('manual_tenant');

                    // Ελέγχουμε αν υπάρχει πίνακας invitations
                    $hasInvitationsTable = DB::connection('manual_tenant')
                        ->select("SHOW TABLES LIKE 'invitations'");

                    if (!empty($hasInvitationsTable)) {
                        // Ελέγχουμε αν υπάρχει η πρόσκληση
                        $invitation = DB::connection('manual_tenant')
                            ->table('invitations')
                            ->where('token', $token)
                            ->first();

                        if ($invitation) {
                            $tenant = $currentTenant;
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Σφάλμα κατά την αναζήτηση της πρόσκλησης στον tenant {$currentTenant->name}: " . $e->getMessage());
                    continue;
                }
            }
        }

        if (!$tenant) {
            return abort(404, 'Η πρόσκληση δεν βρέθηκε.');
        }

        // Συνδεόμαστε στη βάση του tenant
        $dbName = $tenant->database;
        config(['database.connections.manual_tenant' => [
            'driver' => 'mysql',
            'host' => config('database.connections.mysql.host'),
            'port' => config('database.connections.mysql.port'),
            'database' => $dbName,
            'username' => config('database.connections.mysql.username'),
            'password' => config('database.connections.mysql.password'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]]);

        // Καθαρισμός caching
        DB::purge('manual_tenant');

        // Βρίσκουμε την πρόσκληση
        try {
            $invitationData = DB::connection('manual_tenant')
                ->table('invitations')
                ->where('token', $token)
                ->first();

            if (!$invitationData) {
                return abort(404, 'Η πρόσκληση δεν βρέθηκε.');
            }

            // Έλεγχος αν η πρόσκληση έχει λήξει
            $isExpired = $invitationData->expires_at && now()->gt($invitationData->expires_at);
            if ($isExpired) {
                return back()->with('error', 'Η πρόσκληση έχει λήξει.');
            }

            // Έλεγχος αν η πρόσκληση έχει ήδη γίνει αποδεκτή
            $isAccepted = $invitationData->accepted_at !== null;
            if ($isAccepted) {
                return back()->with('error', 'Η πρόσκληση έχει ήδη γίνει αποδεκτή.');
            }

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            // ΔΕΝ δημιουργούμε χρήστη στην κύρια βάση - οι προσκεκλημένοι χρήστες ανήκουν ΜΟΝΟ στη βάση του tenant

            // Δημιουργία χρήστη ΜΟΝΟ στη βάση του tenant
            $hashedPassword = Hash::make($validated['password']);

            // Βρίσκουμε το επόμενο διαθέσιμο ID
            $lastUser = DB::connection('manual_tenant')->table('users')->orderBy('id', 'desc')->first();
            $userId = $lastUser ? $lastUser->id + 1 : 1;

            // Έλεγχος αν ο χρήστης υπάρχει ήδη στη βάση του tenant
            $existingTenantUser = DB::connection('manual_tenant')->table('users')
                ->where('email', $invitationData->email)
                ->first();

            if (!$existingTenantUser) {
                // Εισαγωγή του χρήστη στη βάση του tenant
                DB::connection('manual_tenant')->table('users')->insert([
                    'id' => $userId,
                    'name' => $validated['name'],
                    'email' => $invitationData->email,
                    'password' => $hashedPassword,
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info("Ο χρήστης προστέθηκε στη βάση του tenant: {$tenant->database} με ID: {$userId}");
            } else {
                // Αν υπάρχει, ενημερώνουμε τα στοιχεία του
                $userId = $existingTenantUser->id;

                DB::connection('manual_tenant')->table('users')
                    ->where('id', $userId)
                    ->update([
                        'name' => $validated['name'],
                        'password' => $hashedPassword,
                        'updated_at' => now(),
                    ]);

                Log::info("Ενημερώθηκαν τα στοιχεία του υπάρχοντα χρήστη στη βάση του tenant: ID {$userId}");
            }

            // Έλεγχος αν υπάρχει ο ρόλος
            $role = DB::connection('manual_tenant')->table('roles')
                ->where('name', $invitationData->role)
                ->first();

            if (!$role) {
                // Αν ο ρόλος δεν υπάρχει, τον δημιουργούμε
                Log::info("Δημιουργία του ρόλου '{$invitationData->role}' στη βάση του tenant");

                $roleId = DB::connection('manual_tenant')->table('roles')->insertGetId([
                    'name' => $invitationData->role,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $role = DB::connection('manual_tenant')->table('roles')
                    ->where('id', $roleId)
                    ->first();
            }

            if ($role) {
                // Έλεγχος αν ο ρόλος έχει ήδη ανατεθεί
                $hasRole = DB::connection('manual_tenant')->table('model_has_roles')
                    ->where('role_id', $role->id)
                    ->where('model_type', 'App\\Models\\User')
                    ->where('model_id', $userId)
                    ->first();

                if (!$hasRole) {
                    // Ανάθεση του ρόλου
                    DB::connection('manual_tenant')->table('model_has_roles')->insert([
                        'role_id' => $role->id,
                        'model_type' => 'App\\Models\\User',
                        'model_id' => $userId,
                    ]);

                    Log::info("Ανατέθηκε ο ρόλος '{$invitationData->role}' στον χρήστη με ID: {$userId}");
                } else {
                    Log::info("Ο χρήστης έχει ήδη τον ρόλο '{$invitationData->role}'");
                }
            } else {
                Log::warning("Δεν ήταν δυνατή η δημιουργία του ρόλου '{$invitationData->role}' στη βάση του tenant");
            }

            // Ενημέρωση της πρόσκλησης
            DB::connection('manual_tenant')->table('invitations')
                ->where('token', $token)
                ->update([
                    'accepted_at' => now()
                ]);

            // Δημιουργία session για τον χρήστη
            // Πρέπει να δημιουργήσουμε έναν προσωρινό χρήστη για το Auth
            $tempUser = new User([
                'id' => $userId,
                'name' => $validated['name'],
                'email' => $invitationData->email,
                'password' => $hashedPassword,
            ]);

            // Αυτόματη σύνδεση του χρήστη
            Auth::login($tempUser);

            // Ανακατεύθυνση στο dashboard του tenant
            try {
                // Ελέγχουμε αν το $tenant->domains είναι null ή όχι
                $hasDomains = method_exists($tenant, 'domains') && $tenant->domains !== null;
                $domain = null;

                if ($hasDomains) {
                    $domain = $tenant->domains()->first();
                }

                if ($domain) {
                    Log::info("Ανακατεύθυνση στο dashboard του tenant με domain: {$domain->domain}");
                    return redirect()->route('tenant.dashboard', ['domain' => $domain->domain])
                        ->with('message', 'Καλώς ήρθατε! Η πρόσκληση έγινε αποδεκτή με επιτυχία.');
                } else {
                    Log::info("Ανακατεύθυνση στο κεντρικό dashboard (δεν βρέθηκε domain για το tenant)");
                    return redirect()->route('dashboard')
                        ->with('message', 'Καλώς ήρθατε! Η πρόσκληση έγινε αποδεκτή με επιτυχία.');
                }
            } catch (\Exception $e) {
                Log::error("Σφάλμα κατά την ανακατεύθυνση: " . $e->getMessage());
                // Σε περίπτωση σφάλματος, ανακατευθύνουμε στο κεντρικό dashboard
                return redirect()->route('dashboard')
                    ->with('message', 'Καλώς ήρθατε! Η πρόσκληση έγινε αποδεκτή με επιτυχία.');
            }
        } catch (\Exception $e) {
            Log::error("Σφάλμα κατά την επεξεργασία της πρόσκλησης: " . $e->getMessage());
            return back()->with('error', 'Προέκυψε σφάλμα κατά την επεξεργασία της πρόσκλησης.');
        }
    }

    /**
     * Ακύρωση πρόσκλησης
     */
    public function destroy(Request $request, Invitation $invitation) {
        // Βρίσκουμε τον tenant
        $tenant = $this->findTenantFromRequest($request);

        if (!$tenant) {
            return back()->with('error', 'Δεν βρέθηκε ενεργός tenant. Παρακαλώ χρησιμοποιήστε το σωστό URL.');
        }

        // Αρχικά ελέγχουμε αν ο τρέχων χρήστης υπάρχει στη βάση του tenant
        $tenantUser = null;
        if ($tenant) {
            // Χειροκίνητη σύνδεση στη βάση του tenant
            $dbName = $tenant->database;

            config(['database.connections.manual_tenant' => [
                'driver' => 'mysql',
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port'),
                'database' => $dbName,
                'username' => config('database.connections.mysql.username'),
                'password' => config('database.connections.mysql.password'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ]]);

            // Καθαρισμός caching
            DB::purge('manual_tenant');

            // Ελέγχουμε αν υπάρχει ο χρήστης στον tenant
            $email = Auth::user()->email;
            try {
                $tenantUser = DB::connection('manual_tenant')->table('users')->where('email', $email)->first();
            } catch (\Exception $e) {
                Log::error("Σφάλμα κατά την αναζήτηση χρήστη στη βάση του tenant: " . $e->getMessage());
            }
        }

        // Έλεγχος αν ο χρήστης είναι ο αποστολέας ή έχει τα κατάλληλα δικαιώματα
        $userId = $tenantUser ? $tenantUser->id : 1; // Χρησιμοποιούμε το tenant user ID ή 1 (owner) αν δεν βρέθηκε
        if ($userId !== $invitation->invited_by && !Auth::user()->hasRole(['owner', 'super-admin', 'admin'])) {
            return back()->with('error', 'Δεν έχετε δικαίωμα να ακυρώσετε αυτή την πρόσκληση.');
        }

        $invitation->delete();

        // Βρίσκουμε το domain από το URL
        $path = $request->path();
        $pathSegments = explode('/', $path);
        $domainName = $pathSegments[1] ?? '';

        return redirect()->route('tenant.invitations.index', ['domain' => $domainName])
            ->with('message', 'Η πρόσκληση ακυρώθηκε με επιτυχία.');
    }

    /**
     * Αποστολή πρόσκλησης ξανά
     */
    public function resend(Request $request, Invitation $invitation) {
        // Βρίσκουμε τον tenant
        $tenant = $this->findTenantFromRequest($request);

        if (!$tenant) {
            return back()->with('error', 'Δεν βρέθηκε ενεργός tenant. Παρακαλώ χρησιμοποιήστε το σωστό URL.');
        }

        // Αρχικά ελέγχουμε αν ο τρέχων χρήστης υπάρχει στη βάση του tenant
        $tenantUser = null;
        if ($tenant) {
            // Χειροκίνητη σύνδεση στη βάση του tenant
            $dbName = $tenant->database;

            config(['database.connections.manual_tenant' => [
                'driver' => 'mysql',
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port'),
                'database' => $dbName,
                'username' => config('database.connections.mysql.username'),
                'password' => config('database.connections.mysql.password'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ]]);

            // Καθαρισμός caching
            DB::purge('manual_tenant');

            // Ελέγχουμε αν υπάρχει ο χρήστης στον tenant
            $email = Auth::user()->email;
            try {
                $tenantUser = DB::connection('manual_tenant')->table('users')->where('email', $email)->first();
            } catch (\Exception $e) {
                Log::error("Σφάλμα κατά την αναζήτηση χρήστη στη βάση του tenant: " . $e->getMessage());
            }
        }

        // Έλεγχος αν ο χρήστης είναι ο αποστολέας ή έχει τα κατάλληλα δικαιώματα
        $userId = $tenantUser ? $tenantUser->id : 1; // Χρησιμοποιούμε το tenant user ID ή 1 (owner) αν δεν βρέθηκε
        if ($userId !== $invitation->invited_by && !Auth::user()->hasRole(['owner', 'super-admin', 'admin'])) {
            return back()->with('error', 'Δεν έχετε δικαίωμα να στείλετε ξανά αυτή την πρόσκληση.');
        }

        // Ανανέωση ημερομηνίας λήξης
        $invitation->expires_at = now()->addDays(7);
        $invitation->save();

        // Αποστολή email
        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        // Βρίσκουμε το domain από το URL
        $path = $request->path();
        $pathSegments = explode('/', $path);
        $domainName = $pathSegments[1] ?? '';

        return redirect()->route('tenant.invitations.index', ['domain' => $domainName])
            ->with('message', 'Η πρόσκληση στάλθηκε ξανά με επιτυχία.');
    }
}
