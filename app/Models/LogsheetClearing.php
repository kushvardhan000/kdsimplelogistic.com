<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogsheetClearing extends Model
{
    use HasFactory;

    protected $fillable = [
        'logsheet_id',
        'cleared_by',
        'cleared_at',
        'invoice_no_reference',
        'notes',
    ];

    protected $casts = [
        'cleared_at' => 'datetime',
    ];

    public function logsheet(): BelongsTo
    {
        return $this->belongsTo(Logsheet::class, 'logsheet_id');
    }

    public function clearer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }
}
