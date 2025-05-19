<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
