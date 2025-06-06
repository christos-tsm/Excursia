<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domain extends Model {
    use HasFactory;

    protected $fillable = [
        'domain',
        'tenant_id',
        'is_primary'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function tenant(): BelongsTo {
        return $this->belongsTo(Tenant::class);
    }
}
