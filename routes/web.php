<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantRegisterController;
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
});

// Το γενικό dashboard έχει αφαιρεθεί καθώς δε θα χρησιμοποιείται

// Dashboard για tenants (ταξιδιωτικά γραφεία)
Route::get('/tenant/dashboard', function () {
    return Inertia::render('Tenant/Dashboard');
})->middleware(['auth', 'verified'])->name('tenant.dashboard');

// Ανακατεύθυνση του παλιού dashboard στο tenant dashboard
Route::redirect('/dashboard', '/tenant/dashboard');

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

require __DIR__ . '/auth.php';
