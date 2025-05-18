<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider {
    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void {
        $this->registerPolicies();

        // Καταχώρηση του custom tenant user provider
        Auth::provider('tenant', function ($app, array $config) {
            return new TenantUserProvider($app['hash'], $config['model']);
        });
    }
}
