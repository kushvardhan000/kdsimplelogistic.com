<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\TransportLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $aggregates = Cache::remember('dashboard.aggregates', now()->addMinutes(1), function () {
            $today = now()->startOfDay();
            $monthStart = now()->startOfMonth();

            return [
                'totalEntries' => TransportLog::count(),
                'todayEntries' => TransportLog::where('date', '>=', $today)->count(),
                'todayProfit' => TransportLog::where('date', '>=', $today)->sum('profit'),
                'monthlyProfit' => TransportLog::where('date', '>=', $monthStart)->sum('profit'),
                'pendingVehiclePayments' => TransportLog::where('balance_vehicle_payment', '>', 0)->sum('balance_vehicle_payment'),
                'pendingFuelBalance' => TransportLog::where('fuel_station_balance', '>', 0)->sum('fuel_station_balance'),
                'totalAdvances' => TransportLog::sum('total_advance'),
                'totalExpenses' => TransportLog::sum('total_expense'),
                'pendingFuelSettlementsCount' => TransportLog::whereIn('fuel_payment_status', ['unpaid', 'partial'])->count(),
                'pendingFuelSettlementsDue' => TransportLog::whereIn('fuel_payment_status', ['unpaid', 'partial'])->get()->reduce(function ($carry, $log) {
                    $paid = \App\Models\AccountTransaction::where('reference_type', \App\Models\TransportLog::class)
                        ->where('reference_id', $log->id)
                        ->where('direction', 'credit')
                        ->whereNull('deleted_at')
                        ->sum('amount');

                    return $carry + max(0, (float) $log->diesel_advance - (float) $paid);
                }, 0),
            ];
        });

        $recentLogs = TransportLog::with('creator')
            ->latest('created_at')
            ->limit(8)
            ->get();

        $recentAdminActions = ActivityLog::with('user')
            ->whereHas('user', fn ($q) => $q->where('role', User::ROLE_SUPER_ADMIN))
            ->latest('created_at')
            ->limit(8)
            ->get();

        $viewData = array_merge($aggregates, [
            'recentLogs' => $recentLogs,
            'recentAdminActions' => $recentAdminActions,
        ]);

        if ($request->user()?->isSuperAdmin()) {
            $viewData['recentActivity'] = ActivityLog::with('user')
                ->latest('created_at')
                ->limit(8)
                ->get();

            $viewData['totalFuelStationDues'] = Cache::remember('dashboard.totalFuelStationDues', now()->addMinutes(1), function () {
                return Account::where('type', 'fuel_station')->sum('current_balance');
            });

            $viewData['totalMotorPartsDues'] = Cache::remember('dashboard.totalMotorPartsDues', now()->addMinutes(1), function () {
                return Account::where('type', 'motor_parts_shop')->sum('current_balance');
            });

            $monthStart = now()->startOfMonth();

            $viewData['monthlyStaffSalaryPaid'] = Cache::remember('dashboard.monthlyStaffSalaryPaid', now()->addMinutes(1), function () use ($monthStart) {
                return AccountTransaction::whereHas('account', fn ($q) => $q->where('type', 'staff'))
                    ->where('direction', 'credit')
                    ->whereBetween('transaction_date', [$monthStart, now()->endOfMonth()])
                    ->sum('amount');
            });

            $viewData['monthlyCompanyExpenses'] = Cache::remember('dashboard.monthlyCompanyExpenses', now()->addMinutes(1), function () use ($monthStart) {
                return AccountTransaction::whereHas('account', fn ($q) => $q->where('type', 'company_expense'))
                    ->where('direction', 'debit')
                    ->whereBetween('transaction_date', [$monthStart, now()->endOfMonth()])
                    ->sum('amount');
            });
        }

        return view('dashboard.dashboard', $viewData);
    }
}
