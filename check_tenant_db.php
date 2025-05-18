<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Σύνδεση στη βάση tenant_2mcmpas4
try {
    // Ρύθμιση χειροκίνητης σύνδεσης
    config(['database.connections.manual_tenant' => [
        'driver' => 'mysql',
        'host' => config('database.connections.mysql.host'),
        'port' => config('database.connections.mysql.port'),
        'database' => 'tenant_2mcmpas4',
        'username' => config('database.connections.mysql.username'),
        'password' => config('database.connections.mysql.password'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ]]);

    // Καθαρισμός caching
    \DB::purge('manual_tenant');

    echo "Επιτυχής σύνδεση στη βάση tenant_2mcmpas4\n\n";

    // Έλεγχος αν υπάρχει ο πίνακας users
    $tables = \DB::connection('manual_tenant')->select("SHOW TABLES LIKE 'users'");

    if (empty($tables)) {
        echo "Δεν υπάρχει ο πίνακας 'users' στη βάση του tenant\n";
        exit;
    }

    // Έλεγχος αν υπάρχει ο χρήστης με ID 1
    $user = \DB::connection('manual_tenant')->table('users')->where('id', 1)->first();

    if ($user) {
        echo "Βρέθηκε χρήστης με ID 1:\n";
        echo "- Όνομα: " . $user->name . "\n";
        echo "- Email: " . $user->email . "\n";
        echo "- Δημιουργήθηκε: " . $user->created_at . "\n\n";

        // Έλεγχος αν έχει ανατεθεί ο ρόλος owner
        $role = \DB::connection('manual_tenant')
            ->table('roles')
            ->join('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', 1)
            ->where('roles.name', 'owner')
            ->first();

        if ($role) {
            echo "Ο χρήστης έχει τον ρόλο 'owner'\n";
        } else {
            echo "Ο χρήστης ΔΕΝ έχει τον ρόλο 'owner'\n";
        }
    } else {
        echo "ΔΕΝ βρέθηκε χρήστης με ID 1 στη βάση του tenant\n";
    }

    // Έλεγχος αν υπάρχει χρήστης με το email του owner
    $userByEmail = \DB::connection('manual_tenant')
        ->table('users')
        ->where('email', 'sotiria@mpelou.gr')
        ->first();

    if ($userByEmail) {
        echo "\nΒρέθηκε χρήστης με email 'sotiria@mpelou.gr':\n";
        echo "- ID: " . $userByEmail->id . "\n";
        echo "- Όνομα: " . $userByEmail->name . "\n";
        echo "- Δημιουργήθηκε: " . $userByEmail->created_at . "\n";
    } else {
        echo "\nΔΕΝ βρέθηκε χρήστης με email 'sotiria@mpelou.gr' στη βάση του tenant\n";
    }

    // Εκτύπωση όλων των χρηστών
    echo "\n\nΌλοι οι χρήστες στη βάση του tenant:\n";
    $allUsers = \DB::connection('manual_tenant')->table('users')->get();
    foreach ($allUsers as $u) {
        echo "- ID: {$u->id}, Όνομα: {$u->name}, Email: {$u->email}\n";
    }
} catch (\Exception $e) {
    echo "Σφάλμα: " . $e->getMessage() . "\n";
}
