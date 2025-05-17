<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteToSuperAdmin extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:promote-to-super-admin {email : Email του χρήστη που θα γίνει super-admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Αναβαθμίζει έναν υπάρχοντα χρήστη σε super-admin';

    /**
     * Execute the console command.
     */
    public function handle() {
        $email = $this->argument('email');

        // Έλεγχος αν υπάρχει ο χρήστης
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Ο χρήστης με email {$email} δεν βρέθηκε!");
            return 1;
        }

        // Έλεγχος αν είναι ήδη super-admin
        if ($user->hasRole('super-admin')) {
            $this->info("Ο χρήστης {$user->name} είναι ήδη super-admin!");
            return 0;
        }

        // Αφαίρεση όλων των προηγούμενων ρόλων
        $user->syncRoles(['super-admin']);

        $this->info("Ο χρήστης {$user->name} αναβαθμίστηκε επιτυχώς σε super-admin!");
        $this->table(
            ['Email', 'Όνομα', 'Ρόλος'],
            [[$user->email, $user->name, 'super-admin']]
        );

        return 0;
    }
}
