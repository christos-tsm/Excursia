<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthenticationTest extends TestCase {
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_owner_can_login_with_personal_email(): void {
        // Δημιουργία ρόλου
        Role::create(['name' => 'owner', 'guard_name' => 'web']);

        // Δημιουργία owner χρήστη
        $owner = User::create([
            'name' => 'Test Owner',
            'email' => 'owner@personal.com',
            'password' => Hash::make('password123'),
        ]);

        // Δημιουργία tenant
        $tenant = Tenant::create([
            'name' => 'Test Business',
            'email' => 'business@company.com',
            'database' => 'test_db',
            'is_active' => true,
            'owner_id' => $owner->id,
        ]);

        // Συσχέτιση owner με tenant
        $owner->tenant_id = $tenant->id;
        $owner->save();
        $owner->assignRole('owner');

        // Test login με προσωπικό email
        $response = $this->post('/login', [
            'email' => 'owner@personal.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $this->assertEquals($owner->id, Auth::id());
    }

    public function test_owner_can_login_with_business_email(): void {
        // Δημιουργία ρόλου
        Role::create(['name' => 'owner', 'guard_name' => 'web']);

        // Δημιουργία owner χρήστη
        $owner = User::create([
            'name' => 'Test Owner',
            'email' => 'owner@personal.com',
            'password' => Hash::make('password123'),
        ]);

        // Δημιουργία tenant
        $tenant = Tenant::create([
            'name' => 'Test Business',
            'email' => 'business@company.com',
            'database' => 'test_db',
            'is_active' => true,
            'owner_id' => $owner->id,
        ]);

        // Συσχέτιση owner με tenant
        $owner->tenant_id = $tenant->id;
        $owner->save();
        $owner->assignRole('owner');

        // Test login με επιχειρησιακό email
        $response = $this->post('/login', [
            'email' => 'business@company.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $this->assertEquals($owner->id, Auth::id());
    }

    public function test_inactive_tenant_owner_cannot_login(): void {
        // Δημιουργία ρόλου
        Role::create(['name' => 'owner', 'guard_name' => 'web']);

        // Δημιουργία owner χρήστη
        $owner = User::create([
            'name' => 'Test Owner',
            'email' => 'owner@personal.com',
            'password' => Hash::make('password123'),
        ]);

        // Δημιουργία ανενεργού tenant
        $tenant = Tenant::create([
            'name' => 'Test Business',
            'email' => 'business@company.com',
            'database' => 'test_db',
            'is_active' => false, // Ανενεργό
            'owner_id' => $owner->id,
        ]);

        // Συσχέτιση owner με tenant
        $owner->tenant_id = $tenant->id;
        $owner->save();
        $owner->assignRole('owner');

        // Test login με προσωπικό email - δεν θα πρέπει να επιτρέπεται
        $response = $this->post('/login', [
            'email' => 'owner@personal.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }
}
