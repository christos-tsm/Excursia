<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model {
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'logo',
        'description',
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

    public function trips(): HasMany {
        return $this->hasMany(Trip::class);
    }

    public function invitations(): HasMany {
        return $this->hasMany(Invitation::class);
    }
}
