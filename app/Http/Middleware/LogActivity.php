<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    protected const WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    protected array $ignorePaths = [
        'login',
        'logout',
        'password-reset',
    ];

    protected ?array $originalAttributes = null;

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if (in_array($request->method(), ['PUT', 'PATCH'], true) && $route) {
            $model = $this->resolveBoundModel($route);
            if ($model) {
                $this->originalAttributes = $model->getAttributes();
            }
        }

        $response = $next($request);

        if (! in_array($request->method(), self::WRITE_METHODS, true)) {
            return $response;
        }

        $user = $request->user();

        if (! $user) {
            return $response;
        }

        if ($this->shouldIgnore($request)) {
            return $response;
        }

        $action = strtolower($request->method());
        $tableName = $this->resolveTable($route);
        $recordId = $this->resolveRecordId($route);
        $changes = $this->buildChanges($route, $action);
        $description = $this->describe($action, $tableName, $changes, $recordId);

        ActivityLogger::crud(
            action: match($action) {
                'post' => 'create',
                'put', 'patch' => 'update',
                'delete' => 'delete',
                default => $action,
            },
            module: $tableName ?? 'unknown',
            recordId: $recordId,
            summary: $this->resolveRecordSummary($route),
            changes: $changes,
            user: $user,
            request: $request
        );

        return $response;
    }

    protected function shouldIgnore(Request $request): bool
    {
        foreach ($this->ignorePaths as $segment) {
            if (str_contains($request->path(), $segment)) {
                return true;
            }
        }

        return false;
    }

    protected function resolveTable($route): ?string
    {
        if (! $route) {
            return null;
        }

        return match ($route->getName()) {
            'transport-logs.index', 'transport-logs.show',
            'transport-logs.create', 'transport-logs.store', 'transport-logs.update', 'transport-logs.destroy' => 'transport_logs',
            'users.index', 'users.show', 'users.create', 'users.store', 'users.update', 'users.destroy',
            'users.reset-password', 'users.deactivate', 'users.activate' => 'users',
            'activity-logs.index', 'activity-logs.show' => 'activity_logs',
            'settings.update' => 'settings',
            default => null,
        };
    }

    protected function resolveRecordId($route): ?int
    {
        if (! $route) {
            return null;
        }

        foreach ($route->parameters() as $param) {
            if (is_object($param) && method_exists($param, 'getKey')) {
                return (int) $param->getKey();
            }

            if (is_numeric($param)) {
                return (int) $param;
            }
        }

        return null;
    }

    protected function resolveBoundModel($route): ?Model
    {
        if (! $route) {
            return null;
        }

        foreach ($route->parameters() as $param) {
            if ($param instanceof Model) {
                return $param;
            }
        }

        return null;
    }

    protected function buildChanges($route, string $action): ?array
    {
        if (! in_array($action, ['put', 'patch'], true)) {
            return null;
        }

        $model = $this->resolveBoundModel($route);
        if (! $model || ! $this->originalAttributes) {
            return null;
        }

        $currentAttributes = $model->getAttributes();
        $changes = [];
        $excluded = ['password', 'remember_token'];

        foreach ($this->originalAttributes as $key => $originalValue) {
            if (in_array($key, $excluded, true)) {
                continue;
            }

            if (! array_key_exists($key, $currentAttributes)) {
                continue;
            }

            $currentValue = $currentAttributes[$key];

            if ($originalValue != $currentValue) {
                $changes[] = [
                    'field' => $key,
                    'old' => $originalValue,
                    'new' => $currentValue,
                ];
            }
        }

        return $changes ?: null;
    }

    protected function describe(string $action, ?string $table, ?array $changes, ?int $recordId = null): string
    {
        $label = $table ? str_replace('_', ' ', ucfirst($table)) : 'Resource';

        if ($action === 'patch' || $action === 'put') {
            if ($changes && count($changes) > 0) {
                $fields = collect($changes)->pluck('field')->join(', ');
                $idPart = $recordId ? " #{$recordId}" : '';
                return "Updated {$label}{$idPart}: {$fields} changed";
            }

            $idPart = $recordId ? " #{$recordId}" : '';
            return "Updated {$label}{$idPart}";
        }

        return match ($action) {
            'post' => "Created {$label}",
            'delete' => "Deleted {$label}",
            default => ucfirst($action) . ' ' . $label,
        };
    }

    protected function resolveRecordSummary($route): ?string
    {
        if ($route && $route->getName()) {
            $summaryMap = [
                'transport-logs.create' => 'New log entry',
                'transport-logs.store' => 'New log entry',
                'transport-logs.update' => 'Updated log entry',
                'transport-logs.destroy' => 'Deleted log entry',
            ];
            return $summaryMap[$route->getName()] ?? null;
        }

        return null;
    }
}
