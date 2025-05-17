<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateSuperAdmin extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-super-admin {--email= : Email του διαχειριστή} {--name= : Όνομα του διαχειριστή} {--password= : Κωδικός πρόσβασης}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Δημιουργεί έναν νέο super-admin χρήστη που μπορεί να διαχειρίζεται τουριστικά γραφεία';

    /**
     * Execute the console command.
     */
    public function handle() {
        $email = $this->option('email') ?: $this->ask('Εισάγετε email για τον super-admin');
        $name = $this->option('name') ?: $this->ask('Εισάγετε όνομα για τον super-admin');
        $password = $this->option('password') ?: $this->secret('Εισάγετε κωδικό πρόσβασης');

        // Επαλήθευση του κωδικού
        if (!$this->option('password')) {
            $passwordConfirmation = $this->secret('Επιβεβαιώστε τον κωδικό πρόσβασης');

            if ($password !== $passwordConfirmation) {
                $this->error('Οι κωδικοί πρόσβασης δεν ταιριάζουν.');
                return 1;
            }
        }

        // Επικύρωση δεδομένων
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        // Δημιουργία του super-admin
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        // Ανάθεση του ρόλου super-admin
        $user->assignRole('super-admin');

        $this->info("Super-admin δημιουργήθηκε επιτυχώς!");
        $this->table(
            ['Email', 'Όνομα', 'Ρόλος'],
            [[$user->email, $user->name, 'super-admin']]
        );

        return 0;
    }
}
