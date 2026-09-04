<?php

namespace App\Observers;

use App\Models\AccountTransaction;
use App\Services\FuelSettlementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountTransactionObserver
{
    public function created(AccountTransaction $transaction): void
    {
        $this->recalculateSettlement($transaction);
    }

    public function updated(AccountTransaction $transaction): void
    {
        $this->recalculateSettlement($transaction);
    }

    public function deleted(AccountTransaction $transaction): void
    {
        $this->recalculateSettlement($transaction);
    }

    public function restored(AccountTransaction $transaction): void
    {
        $this->recalculateSettlement($transaction);
    }

    protected function recalculateSettlement(AccountTransaction $transaction): void
    {
        if ($transaction->reference_type !== \App\Models\TransportLog::class) {
            return;
        }

        $log = \App\Models\TransportLog::find($transaction->reference_id);

        if ($log) {
            app(\App\Services\FuelSettlementService::class)->recalculateForLog($log);
        }

        \Illuminate\Support\Facades\Cache::forget('dashboard.aggregates');
        \Illuminate\Support\Facades\Cache::forget('dashboard.totalFuelStationDues');
        \Illuminate\Support\Facades\Cache::forget('dashboard.totalMotorPartsDues');
        \Illuminate\Support\Facades\Cache::forget('dashboard.monthlyStaffSalaryPaid');
        \Illuminate\Support\Facades\Cache::forget('dashboard.monthlyCompanyExpenses');
    }
}
