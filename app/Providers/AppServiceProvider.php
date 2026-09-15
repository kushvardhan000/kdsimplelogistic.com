<?php

namespace App\Providers;

use App\Models\Logsheet;
use App\Observers\LogsheetObserver;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\ActivityLog;
use App\Models\TransportLog;
use App\Models\User;
use App\Observers\AccountTransactionObserver;
use App\Observers\TransportLogObserver;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        TransportLog::class => \App\Policies\TransportLogPolicy::class,
        User::class => \App\Policies\UserPolicy::class,
        ActivityLog::class => \App\Policies\ActivityLogPolicy::class,
        Account::class => \App\Policies\AccountPolicy::class,
        AccountTransaction::class => \App\Policies\AccountTransactionPolicy::class,
    ];

    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        Gate::define('manage-admins', fn (User $user) => $user->isActive() && $user->isSuperAdmin());
        Gate::define('view-activity-logs', fn (User $user) => $user->isActive() && $user->isSuperAdmin());
        Gate::define('access-dashboard', fn (User $user) => $user->isActive());
        Gate::define('activate-user', fn (User $user) => $user->isActive() && $user->isSuperAdmin());

        TransportLog::observe(TransportLogObserver::class);
        AccountTransaction::observe(AccountTransactionObserver::class);
        Logsheet::observe(LogsheetObserver::class);
    }
}
