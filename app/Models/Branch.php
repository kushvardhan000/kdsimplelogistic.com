<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'code', 'address'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    public function fuelStations(): HasMany
    {
        return $this->hasMany(FuelStation::class);
    }

    public function transportLogs(): HasMany
    {
        return $this->hasMany(TransportLog::class);
    }

    public function stationCredits(): HasMany
    {
        return $this->hasMany(StationCredit::class);
    }

    public function stationDebits(): HasMany
    {
        return $this->hasMany(StationDebit::class);
    }
}
