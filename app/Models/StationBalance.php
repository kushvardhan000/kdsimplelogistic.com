<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationBalance extends Model
{
    use HasFactory;
    protected $fillable = ['fuel_station_id', 'total_amount'];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function fuelStation(): BelongsTo
    {
        return $this->belongsTo(FuelStation::class);
    }
}
