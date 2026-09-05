<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'linked_fuel_station_id',
        'linked_driver_id',
        'branch_id',
        'contact_info',
        'address',
        'opening_balance',
        'current_balance',
        'is_active',
        'metadata',
        'aadhar_no',
        'driving_license_no',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'last_transaction_date',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
    }

    public function fuelStation(): BelongsTo
    {
        return $this->belongsTo(FuelStation::class, 'linked_fuel_station_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'linked_driver_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFuelStations($query)
    {
        return $query->where('type', 'fuel_station')
            ->with(['transactions' => fn ($q) => $q->latest()->limit(1)]);
    }

    public function getFormattedBalanceAttribute(): string
    {
        return number_format((float) $this->current_balance, 2);
    }

    public function getLastTransactionDateAttribute(): ?string
    {
        return $this->transactions->first()?->transaction_date?->format('Y-m-d');
    }
}
