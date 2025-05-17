<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    /**
     * Seed the application's database.
     */
    public function run(): void {
        // Πρώτα δημιουργούμε τους ρόλους και τα δικαιώματα
        $this->call(RolesAndPermissionsSeeder::class);

        // Προαιρετικά: Δημιουργία test χρηστών
        // User::factory(10)->create();

        // Προαιρετικά: Δημιουργία test χρήστη
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
