<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Εύρεση του tenant με ID 2
    $tenant = \App\Models\Tenant::find(2);

    if (!$tenant) {
        echo "Δεν βρέθηκε tenant με ID 2\n";
        exit;
    }

    echo "Βρέθηκε tenant: {$tenant->name} (Database: {$tenant->database})\n";

    // Εύρεση του owner
    $owner = \App\Models\User::find($tenant->owner_id);

    if (!$owner) {
        echo "Δεν βρέθηκε ο owner για τον tenant {$tenant->name}\n";
        exit;
    }

    echo "Βρέθηκε owner: {$owner->name} (Email: {$owner->email})\n";

    // Σύνδεση στη βάση του tenant
    config(['database.connections.manual_tenant' => [
        'driver' => 'mysql',
        'host' => config('database.connections.mysql.host'),
        'port' => config('database.connections.mysql.port'),
        'database' => $tenant->database,
        'username' => config('database.connections.mysql.username'),
        'password' => config('database.connections.mysql.password'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ]]);

    // Καθαρισμός caching
    \DB::purge('manual_tenant');

    // Έλεγχος αν ο πίνακας users υπάρχει και δημιουργία του αν όχι
    $tables = \DB::connection('manual_tenant')->select("SHOW TABLES LIKE 'users'");

    if (empty($tables)) {
        echo "Ο πίνακας 'users' δεν υπάρχει, εκτέλεση migration...\n";
        \Artisan::call('tenants:artisan', [
            'artisanCommand' => "migrate --path=database/migrations/tenant/2014_10_12_000000_create_users_table.php --database=tenant",
            '--tenant' => $tenant->id
        ]);
    }

    // Έλεγχος αν ο πίνακας roles υπάρχει και δημιουργία του αν όχι
    $tables = \DB::connection('manual_tenant')->select("SHOW TABLES LIKE 'roles'");

    if (empty($tables)) {
        echo "Ο πίνακας 'roles' δεν υπάρχει, εκτέλεση migration...\n";
        \Artisan::call('tenants:artisan', [
            'artisanCommand' => "migrate --path=database/migrations/tenant/2024_06_23_000002_create_permission_tables.php --database=tenant",
            '--tenant' => $tenant->id
        ]);
    }

    // Έλεγχος αν ο χρήστης υπάρχει ήδη
    $existingUser = \DB::connection('manual_tenant')->table('users')->where('id', 1)->first();

    if (!$existingUser) {
        // Εισαγωγή του χρήστη
        \DB::connection('manual_tenant')->table('users')->insert([
            'id' => 1, // ID 1 για τον owner
            'name' => $owner->name,
            'email' => $owner->email,
            'password' => $owner->password,
            'email_verified_at' => $owner->email_verified_at,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "Επιτυχής εισαγωγή του owner στη βάση του tenant\n";
    } else {
        echo "Ο χρήστης υπάρχει ήδη στη βάση του tenant\n";
    }

    // Έλεγχος αν υπάρχει ο ρόλος owner
    $ownerRole = \DB::connection('manual_tenant')->table('roles')->where('name', 'owner')->first();

    if (!$ownerRole) {
        echo "Ο ρόλος 'owner' δεν υπάρχει, δημιουργία...\n";
        $roleId = \DB::connection('manual_tenant')->table('roles')->insertGetId([
            'name' => 'owner',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    } else {
        $roleId = $ownerRole->id;
    }

    // Έλεγχος αν ο χρήστης έχει ήδη τον ρόλο
    $existingRole = \DB::connection('manual_tenant')->table('model_has_roles')
        ->where('role_id', $roleId)
        ->where('model_type', 'App\\Models\\User')
        ->where('model_id', 1)
        ->first();

    if (!$existingRole) {
        \DB::connection('manual_tenant')->table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => 'App\\Models\\User',
            'model_id' => 1,
        ]);

        echo "Επιτυχής ανάθεση του ρόλου 'owner' στον χρήστη\n";
    } else {
        echo "Ο χρήστης έχει ήδη τον ρόλο 'owner'\n";
    }
} catch (\Exception $e) {
    echo "Σφάλμα: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
