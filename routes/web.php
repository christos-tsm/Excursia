<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantRegisterController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\InvitationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// Ανακατεύθυνση από την παλιά διαδρομή register στη νέα
Route::redirect('/register', '/register-business');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('tenant.register.form'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

// Ανακατεύθυνση του παλιού dashboard στο admin dashboard για διαχειριστές
// και στο tenant dashboard για τους χρήστες tenants
Route::middleware(['auth'])->get('/dashboard', function () {
    $user = Auth::user();
    if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    } elseif ($user->tenant_id) {
        return redirect()->route('tenant.dashboard', ['tenant_id' => $user->tenant_id]);
    } else {
        return redirect('/');
    }
})->name('dashboard');

// Σελίδα αναμονής έγκρισης για tenants
Route::get('/pending', function () {
    return Inertia::render('Admin/Tenants/Pending');
})->middleware(['auth'])->name('admin.tenants.pending');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Διαδρομές για εγγραφή επιχειρήσεων (public)
Route::get('/register-business', [TenantRegisterController::class, 'showRegistrationForm'])
    ->name('tenant.register.form');
Route::post('/register-business', [TenantRegisterController::class, 'register'])
    ->name('tenant.register');

// Διαχείριση επιχειρήσεων (admin)
Route::middleware(['auth', 'role:super-admin|admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    // CRUD για επιχειρήσεις
    Route::resource('tenants', TenantController::class);

    // Έγκριση/Απόρριψη επιχειρήσεων
    Route::post('/tenants/{tenant}/approve', [TenantController::class, 'approve'])
        ->name('tenants.approve');
    Route::post('/tenants/{tenant}/reject', [TenantController::class, 'reject'])
        ->name('tenants.reject');
});

// Διαδρομές για τενάντς - νέα έκδοση με tenant_id
Route::prefix('tenant/{tenant_id}')->middleware(['web', 'auth', 'tenant.access'])->group(function () {
    // Dashboard
    Route::get('/dashboard', function ($tenant_id) {
        $user = Auth::user();
        $tenant = \App\Models\Tenant::findOrFail($tenant_id);

        return Inertia::render('Tenant/Dashboard', [
            'tenant' => $tenant,
        ]);
    })->middleware(['verified'])->name('tenant.dashboard');

    // Trips CRUD
    Route::middleware(['verified'])->name('tenant.')->group(function () {
        Route::get('/trips', [TripController::class, 'index'])->name('trips.index');
        Route::get('/trips/create', [TripController::class, 'create'])->name('trips.create');
        Route::post('/trips', [TripController::class, 'store'])->name('trips.store');
        Route::get('/trips/{trip}', [TripController::class, 'show'])->middleware('tenant.resource')->name('trips.show');
        Route::get('/trips/{trip}/edit', [TripController::class, 'edit'])->middleware('tenant.resource')->name('trips.edit');
        Route::put('/trips/{trip}', [TripController::class, 'update'])->middleware('tenant.resource')->name('trips.update');
        Route::delete('/trips/{trip}', [TripController::class, 'destroy'])->middleware('tenant.resource')->name('trips.destroy');
        Route::post('/trips/{trip}/toggle-publish', [TripController::class, 'togglePublish'])->middleware('tenant.resource')->name('trips.toggle-publish');

        // Invitations
        Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
        Route::get('/invitations/create', [InvitationController::class, 'create'])->name('invitations.create');
        Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
        Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->middleware('tenant.resource')->name('invitations.destroy')->where('invitation', '[0-9]+');
        Route::post('/invitations/{invitation}/resend', [InvitationController::class, 'resend'])->middleware('tenant.resource')->name('invitations.resend')->where('invitation', '[0-9]+');
    });
});

// Διαδρομές για αποδοχή προσκλήσεων (δημόσιες)
Route::get('/invitation/{token}', [InvitationController::class, 'showAcceptForm'])->name('invitation.accept.form');
Route::post('/invitation/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');

require __DIR__ . '/auth.php';
