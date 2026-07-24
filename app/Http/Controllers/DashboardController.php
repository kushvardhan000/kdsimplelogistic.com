<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\TransportLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'dashboard.aggregates.' . ($request->user()?->id ?? 'guest');

        $aggregates = Cache::remember($cacheKey, now()->addMinutes(1), function () {
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
        }

        return view('dashboard.dashboard', $viewData);
    }
}
