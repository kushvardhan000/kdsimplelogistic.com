<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Logsheet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'log_sheet_no',
        'date',
        'vehicle_no',
        'tprt_code',
        'tprt_name',
        'destination',
        'sap_invoice_no',
        'posting_date',
        'bill_date',
        'vendor_inv_no',
        'total_gross_wt',
        'total_booked_amount',
        'total_actual_amount',
        'total_diff',
        'consignment_count',
        'status',
        'cleared_at',
        'cleared_by',
        'last_import_id',
    ];

    protected $casts = [
        'date' => 'date',
        'posting_date' => 'date',
        'bill_date' => 'date',
        'cleared_at' => 'datetime',
        'total_gross_wt' => 'decimal:3',
        'total_booked_amount' => 'decimal:2',
        'total_actual_amount' => 'decimal:2',
        'total_diff' => 'decimal:2',
        'consignment_count' => 'integer',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(LogsheetDetail::class);
    }

    public function clearings(): HasMany
    {
        return $this->hasMany(LogsheetClearing::class);
    }

    public function lastImport(): BelongsTo
    {
        return $this->belongsTo(LogsheetImport::class, 'last_import_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function clearer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }
}