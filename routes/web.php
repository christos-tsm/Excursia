<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantRegisterController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\InvitationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
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

// Το γενικό dashboard έχει αφαιρεθεί καθώς δε θα χρησιμοποιείται

// ΑΦΑΙΡΕΣΗ: Κανονικές διαδρομές για tenants (μέσω subdomain)
// ΑΥΤΟ ΤΟ BLOCK ΘΑ ΑΦΑΙΡΕΘΕΙ ΠΛΗΡΩΣ
//Route::middleware(['auth', 'verified', 'tenant.active'])->prefix('tenant')->name('tenant.')->group(function () {
//    // Dashboard
//    Route::get('/dashboard', function () {
//        return Inertia::render('Tenant/Dashboard');
//    })->name('dashboard');
//
//    // Trips CRUD
//    Route::resource('trips', TripController::class);
//    Route::post('/trips/{trip}/toggle-publish', [TripController::class, 'togglePublish'])->name('trips.toggle-publish');
//
//    // Invitations
//    Route::resource('invitations', InvitationController::class)->except(['show', 'edit', 'update']);
//    Route::post('/invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');
//});

// Ανακατεύθυνση του παλιού dashboard στο tenant dashboard
Route::redirect('/dashboard', '/tenant/dashboard')->name('dashboard');

// Ανακατεύθυνση του /tenant/ URLs στο welcome page με μήνυμα να χρησιμοποιηθεί το σωστό URL
Route::redirect('/tenant', '/')->name('tenant.redirect');
Route::redirect('/tenant/dashboard', '/')->name('tenant.dashboard.redirect');
Route::redirect('/tenant/invitations', '/')->name('tenant.invitations.redirect');
Route::redirect('/tenant/invitations/create', '/')->name('tenant.invitations.create.redirect');
Route::redirect('/tenant/trips', '/')->name('tenant.trips.redirect');

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

// Διαδρομές για τενάντς μέσω path (για τοπική ανάπτυξη αλλά και production)
Route::prefix('tenant/{domain}')->middleware(['web', 'auth', App\Http\Middleware\SetTenantFromPath::class])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Tenant/Dashboard');
    })->middleware(['verified', 'tenant.active'])->name('tenant.dashboard');

    // Trips CRUD (path-based)
    Route::middleware(['verified', 'tenant.active'])->name('tenant.')->group(function () {
        Route::get('/trips', [TripController::class, 'index'])->name('trips.index');
        Route::get('/trips/create', [TripController::class, 'create'])->name('trips.create');
        Route::post('/trips', [TripController::class, 'store'])->name('trips.store');
        Route::get('/trips/{trip}', [TripController::class, 'show'])->name('trips.show');
        Route::get('/trips/{trip}/edit', [TripController::class, 'edit'])->name('trips.edit');
        Route::put('/trips/{trip}', [TripController::class, 'update'])->name('trips.update');
        Route::delete('/trips/{trip}', [TripController::class, 'destroy'])->name('trips.destroy');
        Route::post('/trips/{trip}/toggle-publish', [TripController::class, 'togglePublish'])->name('trips.toggle-publish');

        // Invitations (path-based)
        Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
        Route::get('/invitations/create', [InvitationController::class, 'create'])->name('invitations.create');
        Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
        Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
        Route::post('/invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');
    });
});

// Διαδρομές για αποδοχή προσκλήσεων (δημόσιες)
Route::get('/invitation/{token}', [InvitationController::class, 'showAcceptForm'])->name('invitation.accept.form');
Route::post('/invitation/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');

require __DIR__ . '/auth.php';
