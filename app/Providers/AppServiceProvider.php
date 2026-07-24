<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\TransportLog;
use App\Models\User;
use App\Observers\TransportLogObserver;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        TransportLog::class => \App\Policies\TransportLogPolicy::class,
        User::class => \App\Policies\UserPolicy::class,
        ActivityLog::class => \App\Policies\ActivityLogPolicy::class,
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
    }
}
