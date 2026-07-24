<?php

namespace App\Observers;

use App\Models\StationDebit;
use App\Models\TransportLog;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;

class TransportLogObserver
{
    public function saving(TransportLog $log): void
    {
        $attributes = $log->getAttributes();
        $attributes = $log->computeTotals($attributes);
        $log->forceFill($attributes);

        if ($log->diesel_advance > 0 && $log->fuel_station_id) {
            $creatorId = Auth::id() ?? ($log->created_by ?? null);

            $existing = StationDebit::where('reference_type', 'transport_log')
                ->where('reference_id', $log->id)
                ->whereNull('deleted_at')
                ->first();

            $old = $existing ? $existing->getAttributes() : null;

            $debit = StationDebit::updateOrCreate(
                [
                    'reference_type' => 'transport_log',
                    'reference_id' => $log->id,
                    'deleted_at' => null,
                ],
                [
                    'fuel_station_id' => $log->fuel_station_id,
                    'branch_id' => $log->branch_id,
                    'amount' => $log->diesel_advance,
                    'notes' => 'Diesel advance for ' . $log->date,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'date' => $log->date,
                ]
            );

            $new = $debit->getAttributes();
            $changes = $this->buildDebitChanges($old, $new);
            $this->logDebitActivity($log, $debit, $changes, $existing ? 'updated' : 'created');
        } else {
            $existing = StationDebit::where('reference_type', 'transport_log')
                ->where('reference_id', $log->id)
                ->whereNull('deleted_at')
                ->first();

            if ($existing) {
                $old = $existing->getAttributes();
                $existing->update([
                    'deleted_at' => now(),
                    'updated_by' => Auth::id() ?? $log->updated_by,
                ]);
                $new = $existing->getAttributes();
                $changes = $this->buildDebitChanges($old, $new);
                $this->logDebitActivity($log, $existing, $changes, 'reversed');
            }
        }
    }

    public function deleting(TransportLog $log): void
    {
        if ($log->diesel_advance > 0 && $log->fuel_station_id) {
            $existing = StationDebit::where('reference_type', 'transport_log')
                ->where('reference_id', $log->id)
                ->whereNull('deleted_at')
                ->first();

            if ($existing) {
                $old = $existing->getAttributes();
                $existing->update([
                    'deleted_at' => now(),
                    'updated_by' => Auth::id() ?? $log->updated_by,
                ]);
                $new = $existing->getAttributes();
                $changes = $this->buildDebitChanges($old, $new);
                $this->logDebitActivity($log, $existing, $changes, 'reversed');
            }
        }
    }

    public function saved(TransportLog $log): void
    {
        // The LogActivity middleware handles CRUD activity logging;
        // this observer only handles station debit linked operations.
    }

    protected function buildDebitChanges(?array $old, array $new): array
    {
        $excluded = ['id', 'created_at', 'updated_at', 'created_by', 'updated_by'];
        $changes = [];

        if ($old === null) {
            foreach ($new as $key => $value) {
                if (in_array($key, $excluded, true) || $value === null) {
                    continue;
                }

                $changes[] = [
                    'field' => $key,
                    'old' => null,
                    'new' => $value,
                ];
            }

            return $changes;
        }

        foreach ($old as $key => $oldValue) {
            if (in_array($key, $excluded, true)) {
                continue;
            }

            $newValue = $new[$key] ?? null;

            if ($oldValue != $newValue) {
                $changes[] = [
                    'field' => $key,
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    protected function logDebitActivity(TransportLog $log, StationDebit $debit, array $changes, string $verb): void
    {
        $userId = Auth::id() ?? ($log->updated_by ?? $log->created_by ?? null);

        $fields = collect($changes)->pluck('field')->join(', ');
        $description = match ($verb) {
            'created' => "Created station debit #{$debit->id} for transport log #{$log->id}: {$fields}",
            'updated' => "Updated station debit #{$debit->id} for transport log #{$log->id}: {$fields}",
            'reversed' => "Reversed station debit #{$debit->id} for transport log #{$log->id}: {$fields}",
            default => "Modified station debit #{$debit->id} for transport log #{$log->id}",
        };

        ActivityLogger::log(
            action: $verb === 'created' ? 'create' : ($verb === 'reversed' ? 'delete' : 'update'),
            module: 'station_debits',
            recordId: $debit->id,
            description: $description,
            recordSummary: $log->vehicle_no,
            changes: count($changes) > 0 ? $changes : null,
            user: Auth::user()
        );
    }
}
