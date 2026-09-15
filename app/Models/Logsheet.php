<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Logsheet extends Model
{
    protected $fillable = [
        'date_from',
        'date_to',
        'original_filename',
        'file_path',
        'final_amount',
        'total_rows',
        'uploaded_by',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'final_amount' => 'decimal:2',
    ];

    // one logsheet HAS MANY detail rows
    public function details(): HasMany
    {
        return $this->hasMany(LogsheetDetail::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}