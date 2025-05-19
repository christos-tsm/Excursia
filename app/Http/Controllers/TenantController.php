<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class TenantController extends Controller {
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) {
        $query = Tenant::with('owner')
            ->withCount('domains');

        // Φιλτράρισμα με βάση το email ή το όνομα
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Φιλτράρισμα με βάση την κατάσταση
        if ($request->has('status') && !empty($request->status)) {
            if ($request->status === 'approved') {
                $query->where('is_active', true);
            } elseif ($request->status === 'pending') {
                $query->where('is_active', false);
            }
        }

        $tenants = $query->latest()->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? '',
            ],
            'success' => session('message'),
            'error' => session('error')
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
            'success' => session('message'),
            'error' => session('error')
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tenant $tenant) {
        $tenant->load(['owner', 'domains']);

        return Inertia::render('Admin/Tenants/Edit', [
            'tenant' => $tenant,
            'success' => session('message'),
            'error' => session('error')
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tenant $tenant) {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:tenants,email,' . $tenant->id],
                'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\s]+$/'],
                'description' => ['nullable', 'string'],
            ]);

            // Strip empty spaces from phone field
            $validated['phone'] = preg_replace('/\s+/', '', $validated['phone']);

            $tenant->update($validated);

            return redirect()->route('admin.tenants.show', $tenant)
                ->with('message', 'Η επιχείρηση ενημερώθηκε επιτυχώς');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Προέκυψε σφάλμα: ' . $e->getMessage())
                ->withInput();
        }
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
     * Συγχρονίζει τον owner του tenant με τη βάση δεδομένων του tenant
     */
    private function syncOwnerToTenantDatabase(Tenant $tenant) {
        // Έλεγχος αν ο tenant είναι ενεργός και έχει βάση δεδομένων
        if (!$tenant->is_active) {
            return false;
        }

        try {
            // Χειροκίνητη σύνδεση στη βάση του tenant
            $dbName = $tenant->database;

            Log::info("Προσπάθεια χειροκίνητης σύνδεσης σε βάση: {$dbName}");

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

            // Έλεγχος αν υπάρχει ο πίνακας users - αν όχι, τον δημιουργούμε
            $tables = DB::connection('manual_tenant')->select("SHOW TABLES LIKE 'users'");

            if (empty($tables)) {
                Log::info("Ο πίνακας 'users' δεν υπάρχει, εκτέλεση migration...");
                Artisan::call('tenants:artisan', [
                    'artisanCommand' => "migrate --path=database/migrations/tenant/2014_10_12_000000_create_users_table.php --database=tenant",
                    '--tenant' => $tenant->id
                ]);
            }

            // Έλεγχος αν υπάρχει ο πίνακας roles - αν όχι, τον δημιουργούμε
            $tables = DB::connection('manual_tenant')->select("SHOW TABLES LIKE 'roles'");

            if (empty($tables)) {
                Log::info("Ο πίνακας 'roles' δεν υπάρχει, εκτέλεση migration...");
                Artisan::call('tenants:artisan', [
                    'artisanCommand' => "migrate --path=database/migrations/tenant/2024_06_23_000002_create_permission_tables.php --database=tenant",
                    '--tenant' => $tenant->id
                ]);
            }

            $owner = $tenant->owner;
            if ($owner) {
                // Έλεγχος αν ο χρήστης υπάρχει ήδη στη βάση του tenant
                $existingUser = DB::connection('manual_tenant')->table('users')->where('id', 1)->first();

                if (!$existingUser) {
                    // Δημιουργία του χρήστη στη βάση του tenant μόνο αν δεν υπάρχει
                    DB::connection('manual_tenant')->table('users')->insert([
                        'id' => 1, // Δίνουμε ID 1 για να εξασφαλίσουμε αντιστοιχία
                        'name' => $owner->name,
                        'email' => $owner->email,
                        'password' => $owner->password,
                        'email_verified_at' => $owner->email_verified_at,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info("Owner με ID {$owner->id} αντιγράφηκε επιτυχώς στη βάση του tenant {$tenant->name}");
                } else {
                    Log::info("Owner υπάρχει ήδη στη βάση του tenant {$tenant->name}");
                }

                // Ανάθεση του ρόλου owner στο χρήστη
                $ownerRole = DB::connection('manual_tenant')->table('roles')->where('name', 'owner')->first();

                if (!$ownerRole) {
                    Log::info("Ο ρόλος 'owner' δεν υπάρχει, δημιουργία...");
                    $roleId = DB::connection('manual_tenant')->table('roles')->insertGetId([
                        'name' => 'owner',
                        'guard_name' => 'web',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $roleId = $ownerRole->id;
                }

                // Έλεγχος αν η ανάθεση ρόλου υπάρχει ήδη
                $existingRole = DB::connection('manual_tenant')->table('model_has_roles')
                    ->where('role_id', $roleId)
                    ->where('model_type', 'App\\Models\\User')
                    ->where('model_id', 1)
                    ->first();

                if (!$existingRole) {
                    DB::connection('manual_tenant')->table('model_has_roles')->insert([
                        'role_id' => $roleId,
                        'model_type' => 'App\\Models\\User',
                        'model_id' => 1,
                    ]);
                    Log::info("Ανατέθηκε ο ρόλος 'owner' στον χρήστη");
                } else {
                    Log::info("Ο χρήστης έχει ήδη τον ρόλο 'owner'");
                }

                Log::info("Ολοκλήρωση διαδικασίας αντιγραφής owner για tenant: {$tenant->name}");
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error syncing owner to tenant database: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Approve a tenant
     */
    public function approve(Tenant $tenant) {
        // Έλεγχος αν το tenant είναι ήδη ενεργό
        if ($tenant->is_active) {
            return redirect()->route('admin.tenants.index', $tenant)
                ->with('error', 'Η επιχείρηση είναι ήδη ενεργή');
        }

        try {
            // Ενεργοποίηση του tenant
            $tenant->is_active = true;
            $tenant->save();

            // Δημιουργία των ρόλων και δικαιωμάτων (μία φορά σε επίπεδο εφαρμογής)
            // Reset cached roles and permissions
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            // Δημιουργία δικαιωμάτων για tenant (τουριστικό γραφείο)
            $permissions = [
                'manage trips',
                'manage guides',
                'manage staff',
                'manage settings',
                'view reports',
                'view trips',
                'lead trips'
            ];

            $permissionIds = [];

            foreach ($permissions as $permName) {
                if (!Permission::where('name', $permName)->exists()) {
                    $perm = Permission::create(['name' => $permName, 'guard_name' => 'web']);
                    $permissionIds[$permName] = $perm->id;
                } else {
                    $permissionIds[$permName] = Permission::where('name', $permName)->first()->id;
                }
            }

            // Ρόλοι για tenant (τουριστικό γραφείο)
            $roles = ['owner', 'guide', 'staff'];
            $roleInstances = [];

            foreach ($roles as $roleName) {
                if (!Role::where('name', $roleName)->exists()) {
                    $role = Role::create(['name' => $roleName, 'guard_name' => 'web']);
                    $roleInstances[$roleName] = $role;
                } else {
                    $roleInstances[$roleName] = Role::where('name', $roleName)->first();
                }
            }

            // Ανάθεση αδειών στους ρόλους
            if (isset($roleInstances['owner'])) {
                $ownerPermissions = ['manage trips', 'manage guides', 'manage staff', 'manage settings', 'view reports', 'view trips'];
                foreach ($ownerPermissions as $perm) {
                    if (isset($permissionIds[$perm])) {
                        DB::table('role_has_permissions')->insertOrIgnore([
                            'permission_id' => $permissionIds[$perm],
                            'role_id' => $roleInstances['owner']->id
                        ]);
                    }
                }
            }

            if (isset($roleInstances['guide'])) {
                $guidePermissions = ['view trips', 'lead trips'];
                foreach ($guidePermissions as $perm) {
                    if (isset($permissionIds[$perm])) {
                        DB::table('role_has_permissions')->insertOrIgnore([
                            'permission_id' => $permissionIds[$perm],
                            'role_id' => $roleInstances['guide']->id
                        ]);
                    }
                }
            }

            if (isset($roleInstances['staff'])) {
                if (isset($permissionIds['view trips'])) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $permissionIds['view trips'],
                        'role_id' => $roleInstances['staff']->id
                    ]);
                }
            }

            // Ανάθεση ρόλου "owner" στον ιδιοκτήτη του tenant
            $owner = $tenant->owner;
            if ($owner) {
                $owner->assignRole('owner');
                Log::info("Ανατέθηκε ο ρόλος 'owner' στον χρήστη με ID: {$owner->id}");
            }

            // Ενημέρωση του ιδιοκτήτη με email
            // ... κώδικας για αποστολή email ...

            return redirect()->route('admin.tenants.index', $tenant)
                ->with('message', 'Η επιχείρηση εγκρίθηκε με επιτυχία');
        } catch (\Exception $e) {
            Log::error('Tenant approval error: ' . $e->getMessage());

            return redirect()->route('admin.tenants.index', $tenant)
                ->with('error', 'Προέκυψε σφάλμα κατά την έγκριση: ' . $e->getMessage());
        }
    }

    /**
     * Reject a tenant
     */
    public function reject(Tenant $tenant) {

        // ... κώδικας για αποστολή email απόρριψης ...
        if (!$tenant->is_active) {
            return redirect()->route('admin.tenants.index', $tenant)
                ->with('error', 'Η επιχείρηση έχει ήδη απορριφθεί');
        }

        // Απόρριψη του tenant
        $tenant->is_active = false;
        $tenant->save();

        return redirect()->route('admin.tenants.index', $tenant)
            ->with('message', 'Η κατάσταση έγκρισης της επιχείρησης επαναφέρθηκε σε αναμονή έγκρισης');
    }
}
