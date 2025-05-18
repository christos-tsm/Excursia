<?php

namespace App\Observers;

use App\Models\Domain;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DomainObserver {
    /**
     * Handle the Domain "created" event.
     */
    public function created(Domain $domain): void {
        $this->syncDomainToTenant($domain);
    }

    /**
     * Handle the Domain "updated" event.
     */
    public function updated(Domain $domain): void {
        $this->syncDomainToTenant($domain);
    }

    /**
     * Handle the Domain "deleted" event.
     */
    public function deleted(Domain $domain): void {
        $this->syncDomainToTenant($domain);
    }

    /**
     * Συγχρονίζει το domain στη βάση του tenant.
     */
    private function syncDomainToTenant(Domain $domain): void {
        if (!$domain->tenant_id) {
            Log::warning("Προσπάθεια συγχρονισμού domain χωρίς tenant_id");
            return;
        }

        // Έλεγχος αν ο tenant είναι ενεργός
        $tenant = $domain->tenant;

        if (!$tenant || !$tenant->is_active) {
            Log::info("Παράλειψη συγχρονισμού domain για μη ενεργό tenant");
            return;
        }

        try {
            // Εκτέλεση του command για συγχρονισμό των domains με τη βάση του tenant
            Log::info("Συγχρονισμός domain {$domain->domain} στη βάση του tenant {$tenant->name}");
            Artisan::call('tenants:add-domains-table', [
                '--tenant' => $tenant->id,
                '--force' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Σφάλμα κατά το συγχρονισμό domain: ' . $e->getMessage());
        }
    }
}
