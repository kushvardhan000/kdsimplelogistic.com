<?php

namespace App\Services;

use App\Models\Logsheet;
use App\Models\LogsheetImport;
use App\Models\LogsheetRawRow;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LogsheetImportService
{
    public function import(UploadedFile $file): array
    {
        $user = Auth::user();
        $start = now()->toDateString();
        $filePath = $file->store('logsheets', 'public');

        return DB::transaction(function () use ($file, $user, $start, $filePath) {
            $import = LogsheetImport::create([
                'date_from' => $start,
                'date_to' => $start,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'uploaded_by' => $user?->id,
                'row_count' => 0,
                'consolidated_count' => 0,
                'duplicate_count' => 0,
                'invalid_count' => 0,
                'status' => 'pending',
            ]);

            $rows = Excel::toArray([], $file);
            $sheet = $rows[0] ?? [];
            if (empty($sheet)) {
                $import->update(['status' => 'invalid']);
                return [
                    'rows_imported' => 0,
                    'consolidated' => 0,
                    'duplicates' => 0,
                    'invalid' => 0,
                    'skip' => 'The uploaded workbook is empty.',
                ];
            }

            $headerRow = $this->findHeaderRow($sheet);
            if ($headerRow === null) {
                $import->update(['status' => 'invalid']);
                return [
                    'rows_imported' => 0,
                    'consolidated' => 0,
                    'duplicates' => 0,
                    'invalid' => 0,
                    'skip' => 'Missing Log Sheet No header; cannot identify the client workbook format.',
                ];
            }

            $rawHeaders = array_values($sheet[$headerRow] ?? []);
            $headers = $this->normalizeHeaders($rawHeaders);
            $missing = collect($this->requiredColumns())->diff(array_keys($headers))->values();

            if ($missing->isNotEmpty()) {
                $import->update(['status' => 'invalid']);
                return [
                    'rows_imported' => 0,
                    'consolidated' => 0,
                    'duplicates' => 0,
                    'invalid' => 0,
                    'skip' => 'Missing required columns: ' . $missing->implode(', '),
                ];
            }

            $raw = [];
            $invalid = 0;

            foreach (array_slice($sheet, $headerRow + 1) as $idx => $line) {
                $rowNum = $headerRow + $idx + 2;

                if ($this->isBlankRow($line)) {
                    continue;
                }

                $payload = [];
                foreach ($headers as $canonical => $headIndex) {
                    $payload[$canonical] = $this->cellValue($line[$headIndex] ?? null);
                }

                $payload = $this->normalizePayload($payload);

                $logSheetNo = trim((string) ($payload['log_sheet_no'] ?? ''));
                if ($logSheetNo === '') {
                    $invalid++;
                    LogsheetRawRow::create([
                        'import_id' => $import->id,
                        'log_sheet_no' => null,
                        'raw_data' => $payload,
                        'row_number_in_file' => $rowNum,
                        'is_valid' => false,
                        'validation_error' => 'Missing Log Sheet No',
                    ]);
                    continue;
                }

                $raw[] = [
                    'log_sheet_no' => $logSheetNo,
                    'payload' => $payload,
                    'rowNumber' => $rowNum,
                ];
            }

            $import->update([
                'row_count' => count($raw),
                'consolidated_count' => 0,
                'duplicate_count' => 0,
                'invalid_count' => $invalid,
                'status' => 'completed',
            ]);

            $groups = collect($raw)->groupBy('log_sheet_no');
            $summary = [
                'rows_imported' => count($raw),
                'consolidated' => $groups->count(),
                'duplicates' => 0,
                'invalid' => $invalid,
            ];

            foreach ($groups as $logSheetNo => $items) {
                $payloads = collect($items)->pluck('payload');
                $first = $payloads->first();

                $date = $this->parseDate($first['date'] ?? null);
                $posting = $this->parseDate($first['posting_date'] ?? null);
                $bill = $this->parseDate($first['bill_date'] ?? null);

                $totalGross = (float) round((float) $payloads->sum('gross_wt'), 3);
                $totalBooked = (float) round((float) $payloads->sum('booked_amount'), 2);
                $totalActual = (float) round((float) $payloads->sum('actual_amount'), 2);
                $totalDiff = (float) round((float) $payloads->sum('diff'), 2);

                Logsheet::updateOrCreate(
                    ['log_sheet_no' => $logSheetNo],
                    [
                        'date' => $date,
                        'vehicle_no' => $first['container_id'] ?? null,
                        'tprt_code' => $first['tprt_code'] ?? null,
                        'tprt_name' => $first['tprt_name'] ?? null,
                        'destination' => $first['destination'] ?? null,
                        'sap_invoice_no' => $first['sap_invoice_no'] ?? null,
                        'posting_date' => $posting,
                        'bill_date' => $bill,
                        'vendor_inv_no' => $first['vendor_inv_no'] ?? null,
                        'total_gross_wt' => $totalGross,
                        'total_booked_amount' => $totalBooked,
                        'total_actual_amount' => $totalActual,
                        'total_diff' => $totalDiff,
                        'consignment_count' => $payloads->count(),
                        'status' => 'pending',
                        'last_import_id' => $import->id,
                    ]
                );

                foreach ($items as $item) {
                    LogsheetRawRow::create([
                        'import_id' => $import->id,
                        'log_sheet_no' => $logSheetNo,
                        'raw_data' => $item['payload'],
                        'row_number_in_file' => $item['rowNumber'],
                        'is_valid' => true,
                        'validation_error' => null,
                    ]);
                }
            }

            $import->update([
                'consolidated_count' => $groups->count(),
                'duplicate_count' => 0,
                'invalid_count' => $invalid,
                'status' => 'completed',
            ]);

            return $summary;
        });
    }

    protected function requiredColumns(): array
    {
        return [
            'log_sheet_no',
            'date',
            'invoice_no',
            'inv_date',
            'payer',
            'payer_name',
            'town',
            'gross_wt',
            'volume',
            'tprt_code',
            'tprt_name',
            'container_id',
            'destination',
            'sap_invoice_no',
            'posting_date',
            'bill_date',
            'vendor_inv_no',
            'booked_amount',
            'actual_amount',
            'diff',
        ];
    }

    protected function findHeaderRow(array $sheet): ?int
    {
        foreach ($sheet as $index => $row) {
            $row = array_map(fn ($value) => ! is_string($value) ? (string) $value : trim($value), $row);
            $joined = implode('|', $row);
            if (stripos($joined, 'Log Sheet No') !== false) {
                return $index;
            }
        }

        return null;
    }

    protected function normalizeHeaders(array $rawHeaders): array
    {
        $normalized = [];
        $townSeen = false;

        foreach ($rawHeaders as $index => $header) {
            $key = $this->normalizeHeaderToken((string) $header);

            $canonical = match ($key) {
                'log_sheet_no', 'logsheet_no' => 'log_sheet_no',
                'date' => 'date',
                'invoice_no' => 'invoice_no',
                'inv_date' => 'inv_date',
                'payer' => 'payer',
                'payer_name' => 'payer_name',
                'town' => $townSeen ? 'town_2' : 'town',
                'gross_wt' => 'gross_wt',
                'gross_weight' => 'gross_wt',
                'difference' => 'diff',
                'diff' => 'diff',
                'amount' => 'amount',
                'volume' => 'volume',
                'tprt_code', 'trpt_code' => 'tprt_code',
                'tprt_name', 'trpt_name' => 'tprt_name',
                'container_id' => 'container_id',
                'destination' => 'destination',
                'sapinvoiceno', 'sap_invoice_no' => 'sap_invoice_no',
                'posting_date' => 'posting_date',
                'bill_date' => 'bill_date',
                'vendorinvno', 'vendor_inv_no' => 'vendor_inv_no',
                'route' => 'route',
                'booked_amount' => 'booked_amount',
                'actual_rate' => 'actual_rate',
                'actual_amount' => 'actual_amount',
                default => null,
            };

            if ($key === 'town') {
                $townSeen = true;
            }

            if ($canonical) {
                $normalized[$canonical] = $index;
            }
        }

        return $normalized;
    }

    protected function normalizeHeaderToken(string $header): string
    {
        $key = trim($header);
        $key = strtolower($key);
        $key = str_replace(['-', '/', '(', ')'], '_', $key);
        $key = preg_replace('/\s+/', '_', $key);
        $key = preg_replace('/_+/', '_', $key);
        $key = trim($key, '_');

        return $key;
    }

    protected function normalizePayload(array $payload): array
    {
        foreach (['log_sheet_no', 'invoice_no', 'payer', 'payer_name', 'town', 'tprt_code', 'tprt_name', 'container_id', 'destination', 'sap_invoice_no', 'vendor_inv_no', 'route', 'town_2'] as $field) {
            if (isset($payload[$field])) {
                $payload[$field] = trim((string) $payload[$field]);
            }
        }

        return $payload;
    }

    protected function isBlankRow(array $line): bool
    {
        foreach ($line as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function cellValue($value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return trim((string) $value);
    }

    protected function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return \Carbon\Carbon::createFromFormat('Y-m-d', '1899-12-30')
                ->addDays((int) $value)
                ->format('Y-m-d');
        }

        $trimmed = trim((string) $value);
        if (preg_match('/^0+(\.0+)?$/', $trimmed)) {
            return null;
        }

        $parsed = \Carbon\Carbon::parse($trimmed);
        return $parsed->format('Y-m-d');
    }
}
