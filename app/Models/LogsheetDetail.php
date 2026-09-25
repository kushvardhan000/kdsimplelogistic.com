<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogsheetDetail extends Model
{
    protected $fillable = [
        'logsheet_id', 'log_sheet_no', 'date', 'invoice_no', 'inv_date',
        'payer', 'payer_name', 'town', 'gross_wt', 'difference', 'amount',
        'volume', 'tprt_code', 'tprt_name', 'container_id', 'destination',
        'sap_invoice_no', 'posting_date', 'bill_date', 'vendor_inv_no',
        'route', 'town_2', 'gross_weight_2', 'booked_amount', 'actual_rate',
        'actual_amount', 'diff', 'cleared', 'difference_placeholder',
        'time', 'cust_group', 'no_of_packs',
        'extra_fields',
    ];

    protected $casts = [
        'date' => 'date',
        'inv_date' => 'date',
        'posting_date' => 'date',
        'bill_date' => 'date',
        'cleared' => 'boolean',
        'gross_wt' => 'decimal:3',
        'difference' => 'decimal:3',
        'amount' => 'decimal:2',
        'volume' => 'decimal:3',
        'gross_weight_2' => 'decimal:3',
        'booked_amount' => 'decimal:2',
        'actual_rate' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'diff' => 'decimal:2',
        'difference_placeholder' => 'decimal:3',
        'time' => 'string',
        'cust_group' => 'string',
        'no_of_packs' => 'integer',
        'extra_fields' => 'array',
    ];

    public function logsheet(): BelongsTo
    {
        return $this->belongsTo(Logsheet::class);
    }

    public function rawRow(): BelongsTo
    {
        return $this->belongsTo(LogsheetRawRow::class, 'log_sheet_no', 'log_sheet_no');
    }
}