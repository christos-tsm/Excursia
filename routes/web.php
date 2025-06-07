<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantRegisterController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\TripDocumentController;
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

        // Φορτώνουμε τους ρόλους του χρήστη
        $userRoles = $user->roles->pluck('name')->toArray();

        // Φερνουμε τους χρήστες που ειναι κατω απο τον tenant μαζι με ονόματα, email, roles, τηλεφωνα κλπ.
        $users = \App\Models\User::where('tenant_id', $tenant_id)
            ->with(['roles'])
            ->select('id', 'name', 'email', 'created_at', 'updated_at')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'status' => 'accepted',
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'type' => 'user'
                ];
            });

        // Φερνουμε τις pending προσκλήσεις
        $pendingInvitations = \App\Models\Invitation::where('tenant_id', $tenant_id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->select('id', 'name', 'email', 'role', 'created_at', 'updated_at')
            ->get()
            ->map(function ($invitation) {
                return [
                    'id' => 'invitation_' . $invitation->id,
                    'name' => $invitation->name ?? '-',
                    'email' => $invitation->email,
                    'roles' => [$invitation->role],
                    'status' => 'pending',
                    'created_at' => $invitation->created_at,
                    'updated_at' => $invitation->updated_at,
                    'type' => 'invitation'
                ];
            });

        // Συγχωνεύουμε τους χρήστες και τις προσκλήσεις
        $users = $users->concat($pendingInvitations);

        // return inertia based on the role of the user
        if ($user->hasRole('owner')) {
            return Inertia::render('Tenant/Dashboard/OwnerDashboard', [
                'tenant' => $tenant,
                'userRoles' => $userRoles,
                'users' => $users,
            ]);
        } elseif ($user->hasRole('guide')) {
            return Inertia::render('Tenant/Dashboard/GuideDashboard', [
                'tenant' => $tenant,
                'userRoles' => $userRoles,
            ]);
        } else {
            return Inertia::render('Tenant/Dashboard/StaffDashboard', [
                'tenant' => $tenant,
                'userRoles' => $userRoles,
            ]);
        }
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

        // All Documents (across all trips)
        Route::get('/documents', [TripDocumentController::class, 'allDocuments'])->name('documents.index');

        // Trip Documents
        Route::get('/trips/{trip}/documents', [TripDocumentController::class, 'index'])->middleware('tenant.resource')->name('trip.documents.index');
        Route::get('/trips/{trip}/documents/create', [TripDocumentController::class, 'create'])->middleware('tenant.resource')->name('trip.documents.create');
        Route::get('/trips/{trip}/documents/create-editor', [TripDocumentController::class, 'createEditor'])->middleware('tenant.resource')->name('trip.documents.create-editor');
        Route::post('/trips/{trip}/documents', [TripDocumentController::class, 'store'])->middleware('tenant.resource')->name('trip.documents.store');
        Route::post('/trips/{trip}/documents/store-editor', [TripDocumentController::class, 'storeEditor'])->middleware('tenant.resource')->name('trip.documents.store-editor');
        Route::get('/trips/{trip}/documents/{document}/export/{format}', [TripDocumentController::class, 'export'])->middleware('tenant.resource')->name('trip.documents.export');
        Route::get('/trips/{trip}/documents/{document}/download', [TripDocumentController::class, 'download'])->middleware('tenant.resource')->name('trip.documents.download');
        Route::delete('/trips/{trip}/documents/{document}', [TripDocumentController::class, 'destroy'])->middleware('tenant.resource')->name('trip.documents.destroy');

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
