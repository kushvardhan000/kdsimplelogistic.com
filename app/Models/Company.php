<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'gstin', 'contact_info', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function transportLogsAsCompany(): HasMany
    {
        return $this->hasMany(TransportLog::class, 'company_id');
    }

    public function transportLogsAsCarrier(): HasMany
    {
        return $this->hasMany(TransportLog::class, 'carrier_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
