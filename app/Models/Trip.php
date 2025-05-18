<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Trip extends Model {
    use HasFactory;
    use UsesTenantConnection; // Αυτό εξασφαλίζει ότι το μοντέλο θα χρησιμοποιεί τη σύνδεση του tenant

    protected $fillable = [
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
        'departure_date' => 'date',
        'return_date' => 'date',
        'price' => 'decimal:2',
        'is_published' => 'boolean',
    ];
}
