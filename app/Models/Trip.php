<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model {
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'destination',
        'price',
        'duration',
        'departure_date',
        'return_date',
        'is_published',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'departure_date' => 'date',
        'return_date' => 'date',
        'is_published' => 'boolean',
    ];

    /**
     * Το tenant στο οποίο ανήκει το ταξίδι
     */
    public function tenant(): BelongsTo {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Τα έγγραφα που ανήκουν στο ταξίδι
     */
    public function documents(): HasMany {
        return $this->hasMany(TripDocument::class);
    }

    /**
     * Τα δημόσια έγγραφα του ταξιδιού
     */
    public function publicDocuments(): HasMany {
        return $this->hasMany(TripDocument::class)->where('is_public', true);
    }
}
