<?php

namespace App\Providers;

use App\Models\Domain;
use App\Observers\DomainObserver;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        Vite::prefetch(concurrency: 3);

        // Καταχώρηση του observer για το μοντέλο Domain
        Domain::observe(DomainObserver::class);

        // Δεν χρειάζεται καμία ρύθμιση για τα tenant migrations
        // Απλά βάζουμε τα migrations στο φάκελο database/migrations/tenant
        // και το πακέτο θα τα χρησιμοποιήσει αυτόματα
    }
}
