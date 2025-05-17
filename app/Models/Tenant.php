<?php

namespace App\Models;

use App\TenantFinder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant {
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'logo',
        'description',
        'database',
        'is_active',
        'owner_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function owner(): BelongsTo {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function domains(): HasMany {
        return $this->hasMany(Domain::class);
    }

    public static function getCustomTenantFinder(): string {
        return TenantFinder::class;
    }
}
