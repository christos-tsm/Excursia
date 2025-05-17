<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TenantController extends Controller {
    /**
     * Display a listing of the resource.
     */
    public function index() {
        $tenants = Tenant::with('owner')
            ->withCount('domains')
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {
        return Inertia::render('Admin/Tenants/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {
        // Η λογική για τη δημιουργία tenant από τον admin
        // θα προστεθεί αργότερα, καθώς είναι παρόμοια με το TenantRegisterController
    }

    /**
     * Display the specified resource.
     */
    public function show(Tenant $tenant) {
        $tenant->load(['owner', 'domains']);

        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => $tenant,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tenant $tenant) {
        $tenant->load(['owner', 'domains']);

        return Inertia::render('Admin/Tenants/Edit', [
            'tenant' => $tenant,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tenant $tenant) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:tenants,email,' . $tenant->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
        ]);

        $tenant->update($validated);

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('message', 'Η επιχείρηση ενημερώθηκε επιτυχώς');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant) {
        $tenant->delete();

        return redirect()->route('admin.tenants.index')
            ->with('message', 'Η επιχείρηση διαγράφηκε επιτυχώς');
    }

    /**
     * Approve a tenant
     */
    public function approve(Tenant $tenant) {
        // Έλεγχος αν το tenant είναι ήδη ενεργό
        if ($tenant->is_active) {
            return redirect()->route('admin.tenants.show', $tenant)
                ->with('error', 'Η επιχείρηση είναι ήδη ενεργή');
        }

        // Ενεργοποίηση του tenant
        $tenant->is_active = true;
        $tenant->save();

        // Ενημέρωση του ιδιοκτήτη με email
        // ... κώδικας για αποστολή email ...

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('message', 'Η επιχείρηση εγκρίθηκε με επιτυχία');
    }

    /**
     * Reject a tenant
     */
    public function reject(Tenant $tenant) {
        // Απόρριψη του tenant
        // ... κώδικας για αποστολή email απόρριψης ...

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('message', 'Η επιχείρηση απορρίφθηκε');
    }
}
