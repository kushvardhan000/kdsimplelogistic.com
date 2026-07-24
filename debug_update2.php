<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\DB::listen(function ($query) {
    echo "[SQL] {$query->sql}\n";
    echo "[Bindings] " . json_encode($query->bindings) . "\n\n";
});

$match = App\Models\TransportLog::factory()->create(['clearing_date' => '2025-03-15']);
echo "=== Before update ===\n";
echo "match: vehicle_no={$match->vehicle_no}, profit={$match->profit}, created_at={$match->created_at}, company={$match->company}, clearing_date=" . ($match->clearing_date ?? 'NULL') . "\n";

$match->update([
    'created_at' => '2025-03-15 10:00:00',
    'to_bb_sale' => 1000,
    'paid_sale' => 0,
    'to_pay' => 0,
    'freight' => 0,
    'loading' => 0,
    'unloading' => 0,
    'dd' => 0,
    'tempu_expense' => 0,
    'commission' => 0,
    'dtg_office_expense' => 0,
    'company' => 'Acme Logistics',
    'vehicle_no' => 'MATCH01',
]);
$match->refresh();

echo "=== After update ===\n";
echo "match: vehicle_no={$match->vehicle_no}, profit={$match->profit}, created_at={$match->created_at}, company={$match->company}, clearing_date=" . ($match->clearing_date ?? 'NULL') . "\n";
