<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelStation extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'contact_info',
        'address',
        'is_active',
        'branch_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function balance(): HasMany
    {
        return $this->hasMany(StationBalance::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(StationCredit::class);
    }

    public function debits(): HasMany
    {
        return $this->hasMany(StationDebit::class);
    }

    public function transportLogs(): HasMany
    {
        return $this->hasMany(TransportLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
