<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Invitation extends Model {
    use HasFactory;
    use UsesTenantConnection;

    protected $fillable = [
        'email',
        'name',
        'token',
        'role',
        'invited_by',
        'expires_at'
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Σχέση με τον χρήστη που έστειλε την πρόσκληση
     */
    public function inviter(): BelongsTo {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Έλεγχος αν η πρόσκληση έχει λήξει
     */
    public function isExpired(): bool {
        return $this->expires_at->isPast();
    }

    /**
     * Έλεγχος αν η πρόσκληση έχει γίνει αποδεκτή
     */
    public function isAccepted(): bool {
        return !is_null($this->accepted_at);
    }

    /**
     * Αποδοχή της πρόσκλησης
     */
    public function accept(): void {
        $this->accepted_at = now();
        $this->save();
    }

    /**
     * Δημιουργία URL αποδοχής πρόσκλησης
     */
    public function getAcceptUrl(): string {
        return URL::signedRoute('invitation.accept', ['token' => $this->token]);
    }
}
