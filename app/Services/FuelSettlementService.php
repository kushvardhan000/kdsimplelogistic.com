<?php

namespace App\Services;

use App\Models\AccountTransaction;
use App\Models\TransportLog;
use Illuminate\Support\Facades\DB;

class FuelSettlementService
{
    public function recalculateForLog(TransportLog $log): void
    {
        DB::transaction(function () use ($log) {
            $hasFuelComponent = $log->fuel_station_id && (float) $log->diesel_advance > 0;

            if (! $hasFuelComponent) {
                $log->forceFill([
                    'fuel_paid_amount' => 0,
                    'fuel_payment_status' => null,
                ])->saveQuietly();
                return;
            }

            $paid = AccountTransaction::where('reference_type', TransportLog::class)
                ->where('reference_id', $log->id)
                ->where('direction', 'credit')
                ->whereNull('deleted_at')
                ->sum('amount');

            $paid = round((float) $paid, 2);
            $advance = round((float) $log->diesel_advance, 2);

            $status = match (true) {
                $paid > $advance => 'overpaid',
                $paid === $advance && $advance > 0 => 'paid',
                $paid > 0 => 'partial',
                default => 'unpaid',
            };

            $log->forceFill([
                'fuel_paid_amount' => $paid,
                'fuel_payment_status' => $status,
            ])->saveQuietly();
        });
    }

    public function getPaymentHistoryForLog(TransportLog $log)
    {
        return AccountTransaction::where('reference_type', TransportLog::class)
            ->where('reference_id', $log->id)
            ->where('direction', 'credit')
            ->whereNull('deleted_at')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();
    }

    public function getRemainingDue(TransportLog $log): float
    {
        $advance = max(0, (float) $log->diesel_advance);
        $paid = max(0, (float) $log->fuel_paid_amount);

        return max(0, round($advance - $paid, 2));
    }
}
