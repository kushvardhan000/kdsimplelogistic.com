<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogsheetImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_from',
        'date_to',
        'original_filename',
        'file_path',
        'uploaded_by',
        'row_count',
        'consolidated_count',
        'duplicate_count',
        'invalid_count',
        'status',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function rawRows(): HasMany
    {
        return $this->hasMany(LogsheetRawRow::class, 'import_id');
    }

    public function logsheets(): HasMany
    {
        return $this->hasMany(Logsheet::class, 'last_import_id');
    }
}
