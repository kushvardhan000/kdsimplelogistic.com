<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(
        string $action,
        string $module,
        ?int $recordId = null,
        ?string $description = null,
        ?string $recordSummary = null,
        ?array $changes = null,
        ?bool $success = true,
        ?string $method = null,
        ?string $url = null,
        ?User $user = null
    ): void {
        try {
            $user = $user ?? Auth::user();
            $request = request();

            $isStructuredChanges = $changes && isset($changes[0]) && is_array($changes[0]) && isset($changes[0]['field']);

            ActivityLog::create([
                'user_id' => $user?->id,
                'role' => $user?->role ?? null,
                'action' => $action,
                'table_name' => $module,
                'record_id' => $recordId,
                'record_summary' => $recordSummary,
                'description' => $description,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'success' => $success ?? true,
                'method' => $method ?? $request?->getMethod(),
                'url' => $url ?? $request?->fullUrl(),
                'old_values' => $isStructuredChanges ? null : ($changes['old'] ?? null),
                'new_values' => $isStructuredChanges ? null : ($changes['new'] ?? null),
                'changes' => $changes,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function login(User $user, Request $request): void
    {
        self::log(
            action: 'login',
            module: 'users',
            recordId: $user->id,
            description: 'User logged in',
            recordSummary: $user->name,
            success: true,
            method: $request->getMethod(),
            url: $request->fullUrl(),
            user: $user
        );
    }

    public static function logout(?User $user, Request $request): void
    {
        self::log(
            action: 'logout',
            module: 'users',
            recordId: $user?->id,
            description: 'User logged out',
            recordSummary: $user?->name,
            success: true,
            method: $request->getMethod(),
            url: $request->fullUrl(),
            user: $user
        );
    }

    public static function failedLogin(string $email, Request $request): void
    {
        self::log(
            action: 'failed_login',
            module: 'users',
            recordId: null,
            description: "Failed login attempt for {$email}",
            recordSummary: $email,
            success: false,
            method: $request->getMethod(),
            url: $request->fullUrl(),
            user: null
        );
    }

    public static function crud(string $action, string $module, ?int $recordId, ?string $summary, ?array $changes = null, ?User $user = null, ?Request $request = null): void
    {
        $description = match ($action) {
            'create' => "Created {$module}" . ($recordId ? " #{$recordId}" : ''),
            'update' => "Updated {$module}" . ($recordId ? " #{$recordId}" : ''),
            'delete' => "Deleted {$module}" . ($recordId ? " #{$recordId}" : ''),
            default => ucfirst($action) . " {$module}",
        };

        if ($changes && ! empty($changes['field'])) {
            $description .= ': ' . $changes['field'] . ' changed';
        }

        self::log(
            action: $action,
            module: $module,
            recordId: $recordId,
            description: $description,
            recordSummary: $summary,
            changes: $changes,
            success: true,
            method: $request?->getMethod(),
            url: $request?->fullUrl(),
            user: $user
        );
    }

    public static function export(string $module, ?int $recordId, ?string $summary, ?User $user = null, ?Request $request = null): void
    {
        self::log(
            action: 'export',
            module: $module,
            recordId: $recordId,
            description: "Exported {$module}" . ($recordId ? " #{$recordId}" : ''),
            recordSummary: $summary,
            success: true,
            method: $request?->getMethod(),
            url: $request?->fullUrl(),
            user: $user
        );
    }

    public static function statusChange(string $module, ?int $recordId, ?string $summary, string $oldStatus, string $newStatus, ?User $user = null, ?Request $request = null): void
    {
        self::log(
            action: 'status_change',
            module: $module,
            recordId: $recordId,
            description: "Status changed from {$oldStatus} to {$newStatus} for {$module}" . ($recordId ? " #{$recordId}" : ''),
            recordSummary: $summary,
            changes: [
                'field' => 'status',
                'old' => $oldStatus,
                'new' => $newStatus,
            ],
            success: true,
            method: $request?->getMethod(),
            url: $request?->fullUrl(),
            user: $user
        );
    }

    public static function profileUpdate(User $user, ?array $changes = null, ?Request $request = null): void
    {
        self::log(
            action: 'update',
            module: 'profile',
            recordId: $user->id,
            description: 'Profile updated',
            recordSummary: $user->name,
            changes: $changes ? ['field' => collect($changes)->pluck('field')->join(', '), 'old' => null, 'new' => null] : null,
            success: true,
            method: $request?->getMethod(),
            url: $request?->fullUrl(),
            user: $user
        );
    }

    public static function passwordChange(User $user, ?Request $request = null): void
    {
        self::log(
            action: 'password_change',
            module: 'users',
            recordId: $user->id,
            description: 'Password changed',
            recordSummary: $user->name,
            success: true,
            method: $request?->getMethod(),
            url: $request?->fullUrl(),
            user: $user
        );
    }

    public static function roleChange(User $user, string $oldRole, string $newRole, ?Request $request = null): void
    {
        self::log(
            action: 'role_change',
            module: 'users',
            recordId: $user->id,
            description: "Role changed from {$oldRole} to {$newRole}",
            recordSummary: $user->name,
            changes: [
                'field' => 'role',
                'old' => $oldRole,
                'new' => $newRole,
            ],
            success: true,
            method: $request?->getMethod(),
            url: $request?->fullUrl(),
            user: $request?->user()
        );
    }

    public static function permissionChange(User $user, string $permission, string $oldValue, string $newValue, ?Request $request = null): void
    {
        self::log(
            action: 'permission_change',
            module: 'permissions',
            recordId: $user->id,
            description: "Permission '{$permission}' changed from '{$oldValue}' to '{$newValue}'",
            recordSummary: $user->name,
            changes: [
                'field' => 'permission_' . $permission,
                'old' => $oldValue,
                'new' => $newValue,
            ],
            success: true,
            method: $request?->getMethod(),
            url: $request?->fullUrl(),
            user: $request?->user()
        );
    }
}
