<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;
    protected $fillable = [
        'vehicle_no',
        'owner_name',
        'type',
        'capacity_kg',
        'mileage_baseline',
        'is_active',
        'branch_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity_kg' => 'decimal:2',
        'mileage_baseline' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function transportLogs(): HasMany
    {
        return $this->hasMany(TransportLog::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
