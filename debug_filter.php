<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$match = App\Models\TransportLog::factory()->create();
$mismatch1 = App\Models\TransportLog::factory()->create();
$mismatch2 = App\Models\TransportLog::factory()->create(['clearing_date' => '2025-03-15']);
$mismatch3 = App\Models\TransportLog::factory()->create(['clearing_date' => '2025-03-15']);

echo "=== Before update ===\n";
echo "match: vehicle_no={$match->vehicle_no}, profit={$match->profit}, created_at={$match->created_at}, company={$match->company}, clearing_date={$match->clearing_date}\n";

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
echo "match: vehicle_no={$match->vehicle_no}, profit={$match->profit}, created_at={$match->created_at}, company={$match->company}, clearing_date={$match->clearing_date}\n";

$query = App\Models\TransportLog::query();
if ('MATCH') {
    $search = 'MATCH';
    $query->where(function ($q) use ($search) {
        $q->where('vehicle_no', 'like', "%{$search}%")
          ->orWhere('company', 'like', "%{$search}%")
          ->orWhere('transport_name', 'like', "%{$search}%");
    });
}
if ('Acme') {
    $query->where('company', 'like', '%Acme%');
}
if ('profit') {
    $query->where('profit', '>', 0);
}
if ('2025-03-15') {
    $query->whereDate('created_at', '2025-03-15');
}
if ('2025-03-15') {
    $query->whereDate('clearing_date', '2025-03-15');
}

$results = $query->get();
echo "\n=== Filter results ===\n";
echo "Count: " . $results->count() . "\n";
foreach ($results as $r) {
    echo "  vehicle_no={$r->vehicle_no}, profit={$r->profit}, created_at={$r->created_at}, company={$r->company}, clearing_date={$r->clearing_date}\n";
}
