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
        $query = Trip::query();

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
    public function create() {
        return Inertia::render('Tenant/Trips/Create');
    }

    /**
     * Αποθήκευση νέου ταξιδιού
     */
    public function store(Request $request) {
        try {
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

            $trip = Trip::create($validated);

            return redirect()->route('tenant.trips.show', $trip)
                ->with('message', 'Το ταξίδι δημιουργήθηκε επιτυχώς');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Προέκυψε σφάλμα: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Εμφάνιση λεπτομερειών ταξιδιού
     */
    public function show(Trip $trip) {
        return Inertia::render('Tenant/Trips/Show', [
            'trip' => $trip,
            'success' => session('message'),
            'error' => session('error')
        ]);
    }

    /**
     * Εμφάνιση φόρμας επεξεργασίας ταξιδιού
     */
    public function edit(Trip $trip) {
        return Inertia::render('Tenant/Trips/Edit', [
            'trip' => $trip,
            'success' => session('message'),
            'error' => session('error')
        ]);
    }

    /**
     * Ενημέρωση ταξιδιού
     */
    public function update(Request $request, Trip $trip) {
        try {
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

            return redirect()->route('tenant.trips.show', $trip)
                ->with('message', 'Το ταξίδι ενημερώθηκε επιτυχώς');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Προέκυψε σφάλμα: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Διαγραφή ταξιδιού
     */
    public function destroy(Trip $trip) {
        try {
            $trip->delete();

            return redirect()->route('tenant.trips.index')
                ->with('message', 'Το ταξίδι διαγράφηκε επιτυχώς');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Προέκυψε σφάλμα κατά τη διαγραφή: ' . $e->getMessage());
        }
    }

    /**
     * Δημοσίευση/απόσυρση ταξιδιού
     */
    public function togglePublish(Trip $trip) {
        $trip->is_published = !$trip->is_published;
        $trip->save();

        $message = $trip->is_published
            ? 'Το ταξίδι δημοσιεύτηκε επιτυχώς'
            : 'Το ταξίδι αποσύρθηκε από τη δημοσίευση';

        return back()->with('message', $message);
    }
}
