<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TripController extends Controller {
    /**
     * Εμφάνιση λίστας ταξιδιών
     */
    public function index(Request $request) {
        $tenant_id = $request->route('tenant_id');
        $query = Trip::where('tenant_id', $tenant_id);

        // Φιλτράρισμα με βάση τον τίτλο ή τον προορισμό
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%");
            });
        }

        // Φιλτράρισμα με βάση την κατάσταση δημοσίευσης
        if ($request->has('status') && !empty($request->status)) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            }
        }

        $trips = $query->latest()->paginate(10)
            ->withQueryString();

        return Inertia::render('Tenant/Trips/Index', [
            'trips' => $trips,
            'tenant_id' => $tenant_id,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? '',
            ],
            'success' => session('message'),
            'error' => session('error')
        ]);
    }

    /**
     * Εμφάνιση φόρμας δημιουργίας ταξιδιού
     */
    public function create(Request $request) {
        $tenant_id = $request->route('tenant_id');
        return Inertia::render('Tenant/Trips/Create', [
            'tenant_id' => $tenant_id
        ]);
    }

    /**
     * Αποθήκευση νέου ταξιδιού
     */
    public function store(Request $request) {
        try {
            $tenant_id = $request->route('tenant_id');

            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'destination' => ['required', 'string', 'max:255'],
                'price' => ['required', 'numeric', 'min:0'],
                'duration' => ['required', 'integer', 'min:1'],
                'departure_date' => ['nullable', 'date'],
                'return_date' => ['nullable', 'date', 'after_or_equal:departure_date'],
                'is_published' => ['boolean'],
            ]);

            // Προσθήκη του tenant_id
            $validated['tenant_id'] = $tenant_id;

            $trip = Trip::create($validated);

            return redirect()->route('tenant.trips.show', [
                'tenant_id' => $tenant_id,
                'trip' => $trip->id
            ])->with('message', 'Το ταξίδι δημιουργήθηκε επιτυχώς');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Προέκυψε σφάλμα: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Εμφάνιση λεπτομερειών ταξιδιού
     */
    public function show(Request $request, Trip $trip) {
        $tenant_id = $request->route('tenant_id');

        return Inertia::render('Tenant/Trips/Show', [
            'trip' => $trip,
            'tenant_id' => $tenant_id,
            'success' => session('message'),
            'error' => session('error')
        ]);
    }

    /**
     * Εμφάνιση φόρμας επεξεργασίας ταξιδιού
     */
    public function edit(Request $request, Trip $trip) {
        $tenant_id = $request->route('tenant_id');

        return Inertia::render('Tenant/Trips/Edit', [
            'trip' => $trip,
            'tenant_id' => $tenant_id,
            'success' => session('message'),
            'error' => session('error')
        ]);
    }

    /**
     * Ενημέρωση ταξιδιού
     */
    public function update(Request $request, Trip $trip) {
        try {
            $tenant_id = $request->route('tenant_id');

            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'destination' => ['required', 'string', 'max:255'],
                'price' => ['required', 'numeric', 'min:0'],
                'duration' => ['required', 'integer', 'min:1'],
                'departure_date' => ['nullable', 'date'],
                'return_date' => ['nullable', 'date', 'after_or_equal:departure_date'],
                'is_published' => ['boolean'],
            ]);

            $trip->update($validated);

            return redirect()->route('tenant.trips.show', [
                'tenant_id' => $tenant_id,
                'trip' => $trip->id
            ])->with('message', 'Το ταξίδι ενημερώθηκε επιτυχώς');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Προέκυψε σφάλμα: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Διαγραφή ταξιδιού
     */
    public function destroy(Request $request, Trip $trip) {
        try {
            $tenant_id = $request->route('tenant_id');

            $trip->delete();

            return redirect()->route('tenant.trips.index', ['tenant_id' => $tenant_id])
                ->with('message', 'Το ταξίδι διαγράφηκε επιτυχώς');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Προέκυψε σφάλμα κατά τη διαγραφή: ' . $e->getMessage());
        }
    }

    /**
     * Δημοσίευση/απόσυρση ταξιδιού
     */
    public function togglePublish(Request $request, Trip $trip) {
        $tenant_id = $request->route('tenant_id');

        $trip->is_published = !$trip->is_published;
        $trip->save();

        $message = $trip->is_published
            ? 'Το ταξίδι δημοσιεύτηκε επιτυχώς'
            : 'Το ταξίδι αποσύρθηκε από τη δημοσίευση';

        return back()->with('message', $message);
    }
}
