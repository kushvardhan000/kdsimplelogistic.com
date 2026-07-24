<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StationDebit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'fuel_station_id',
        'branch_id',
        'amount',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
        'updated_by',
        'date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    public function fuelStation(): BelongsTo
    {
        return $this->belongsTo(FuelStation::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
