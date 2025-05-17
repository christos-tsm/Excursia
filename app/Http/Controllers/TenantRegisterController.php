<?php

namespace App\Http\Controllers;

use App\Http\Requests\TenantRegisterRequest;
use App\Models\Domain;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TenantRegisterController extends Controller {
    /**
     * Εμφάνιση φόρμας εγγραφής για νέα επιχείρηση
     */
    public function showRegistrationForm() {
        return Inertia::render('Tenants/Register');
    }

    /**
     * Αποθήκευση νέας επιχείρησης
     */
    public function register(TenantRegisterRequest $request) {
        $validated = $request->validated();

        // Αντί για transaction που μπορεί να προκαλέσει timeout, θα χειριστούμε τυχόν σφάλματα με try-catch
        try {
            // 1. Δημιουργία του χρήστη-ιδιοκτήτη
            $owner = User::create([
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'password' => Hash::make($validated['owner_password']),
            ]);

            // 2. Ανάθεση ρόλου owner
            $owner->assignRole('owner');

            // 3. Δημιουργία του tenant (επιχείρησης)
            // Απλοποιημένο όνομα βάσης για να αποφύγουμε προβλήματα
            $tenantDbName = 'tenant_' . strtolower(Str::random(8));

            // Εάν δεν παρέχεται domain, χρησιμοποιούμε το όνομα της επιχείρησης
            $domainName = isset($validated['domain']) && !empty($validated['domain'])
                ? $validated['domain']
                : Str::slug($validated['name']);

            $tenant = Tenant::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'description' => $validated['description'] ?? null,
                'database' => $tenantDbName,
                'is_active' => false, // Ξεκινάει ως ανενεργό, περιμένει έγκριση
                'owner_id' => $owner->id,
            ]);

            // 4. Ενημέρωση του χρήστη με το tenant_id
            $owner->tenant_id = $tenant->id;
            $owner->save();

            // 5. Δημιουργία του domain
            Domain::create([
                'domain' => $domainName,
                'tenant_id' => $tenant->id,
                'is_primary' => true,
            ]);

            // 6. Επιστροφή σελίδας επιβεβαίωσης
            return Inertia::render('Tenants/RegisterSuccess', [
                'tenant' => $tenant->only('name', 'email'),
            ]);
        } catch (\Exception $e) {
            // Καταγραφή του σφάλματος
            Log::error('Tenant registration error: ' . $e->getMessage());

            // Επιστροφή με σφάλμα
            return back()->withErrors([
                'general' => 'Παρουσιάστηκε σφάλμα κατά την εγγραφή. Παρακαλώ δοκιμάστε ξανά ή επικοινωνήστε με τον διαχειριστή.'
            ])->withInput();
        }
    }
}
