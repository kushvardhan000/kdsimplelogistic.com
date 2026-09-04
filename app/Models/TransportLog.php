<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransportLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'date',
        'vehicle_no',
        'company',
        'transport_name',
        'logsheet_no',
        'trace_code',
        'destination',
        'km',
        'weight',
        'to_bb_sale',
        'paid_sale',
        'to_pay',
        'total_sale',
        'freight',
        'loading',
        'unloading',
        'dd',
        'tempu_expense',
        'commission',
        'total_expense',
        'profit',
        'diesel_advance',
        'cash_advance',
        'total_advance',
        'payment',
        'fuel_station_name',
        'fuel_station_balance',
        'balance_vehicle_payment',
        'clearing_date',
        'detail',
        'remarks',
        'mileage',
        'dtg_office_expense',
        'created_by',
        'updated_by',
        'vehicle_id',
        'company_id',
        'carrier_id',
        'branch_id',
        'fuel_station_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'date' => 'date',
        'clearing_date' => 'date',
        'km' => 'decimal:2',
        'weight' => 'decimal:2',
        'to_bb_sale' => 'decimal:2',
        'paid_sale' => 'decimal:2',
        'to_pay' => 'decimal:2',
        'total_sale' => 'decimal:2',
        'freight' => 'decimal:2',
        'loading' => 'decimal:2',
        'unloading' => 'decimal:2',
        'dd' => 'decimal:2',
        'tempu_expense' => 'decimal:2',
        'commission' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'profit' => 'decimal:2',
        'diesel_advance' => 'decimal:2',
        'cash_advance' => 'decimal:2',
        'total_advance' => 'decimal:2',
        'payment' => 'decimal:2',
        'fuel_station_balance' => 'decimal:2',
        'balance_vehicle_payment' => 'decimal:2',
        'mileage' => 'decimal:2',
        'dtg_office_expense' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'carrier_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function fuelStation(): BelongsTo
    {
        return $this->belongsTo(FuelStation::class);
    }

    public function profitColorClass(): string
    {
        return match (true) {
            $this->profit > 0 => 'text-emerald-600 dark:text-emerald-400',
            $this->profit < 0 => 'text-red-600 dark:text-red-400',
            default => 'text-zinc-600 dark:text-zinc-400',
        };
    }

    public function statusBadge(): string
    {
        return $this->clearing_date ? 'Cleared' : 'Pending';
    }

    public function statusBadgeVariant(): string
    {
        return $this->clearing_date ? 'success' : 'warning';
    }

    public function computeTotals(array $data): array
    {
        $data['total_sale'] = round(
            (float) ($data['to_bb_sale'] ?? 0)
            + (float) ($data['paid_sale'] ?? 0)
            + (float) ($data['to_pay'] ?? 0),
            2
        );

        $data['total_advance'] = round(
            (float) ($data['diesel_advance'] ?? 0)
            + (float) ($data['cash_advance'] ?? 0),
            2
        );

        $data['total_expense'] = round(
            (float) ($data['freight'] ?? 0)
            + (float) ($data['loading'] ?? 0)
            + (float) ($data['unloading'] ?? 0)
            + (float) ($data['dd'] ?? 0)
            + (float) ($data['tempu_expense'] ?? 0)
            + (float) ($data['commission'] ?? 0)
            + (float) ($data['dtg_office_expense'] ?? 0),
            2
        );

        $data['profit'] = round($data['total_sale'] - $data['total_expense'], 2);

        $data['balance_vehicle_payment'] = round($data['total_sale'] - $data['payment'], 2);

        return $data;
    }
}
