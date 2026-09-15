<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogsheetRawRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_id',
        'log_sheet_no',
        'raw_data',
        'row_number_in_file',
        'is_valid',
        'validation_error',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'is_valid' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(LogsheetImport::class, 'import_id');
    }
}
