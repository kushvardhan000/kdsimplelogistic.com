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
        'actual_amount', 'diff', 'cleared',
    ];

    protected $casts = [
        'date' => 'date',
        'inv_date' => 'date',
        'posting_date' => 'date',
        'bill_date' => 'date',
        'cleared' => 'boolean',
    ];

    // each detail row BELONGS TO one logsheet
    public function logsheet(): BelongsTo
    {
        return $this->belongsTo(Logsheet::class);
    }
}