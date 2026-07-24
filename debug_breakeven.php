<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$breakevenLog = App\Models\TransportLog::factory()->create();
$profitLog = App\Models\TransportLog::factory()->create();

echo "breakevenLog ID: {$breakevenLog->id}, vehicle: {$breakevenLog->vehicle_no}\n";
echo "profitLog ID: {$profitLog->id}, vehicle: {$profitLog->vehicle_no}\n";

$breakevenLog->update([
    'to_bb_sale' => 1000,
    'paid_sale' => 0,
    'to_pay' => 0,
    'freight' => 1000,
    'loading' => 0,
    'unloading' => 0,
    'dd' => 0,
    'tempu_expense' => 0,
    'commission' => 0,
    'dtg_office_expense' => 0,
]);
$breakevenLog->refresh();

$profitLog->update([
    'to_bb_sale' => 3000,
    'paid_sale' => 0,
    'to_pay' => 0,
    'freight' => 0,
    'loading' => 0,
    'unloading' => 0,
    'dd' => 0,
    'tempu_expense' => 0,
    'commission' => 0,
    'dtg_office_expense' => 0,
]);
$profitLog->refresh();

echo "After updates:\n";
echo "breakevenLog profit: {$breakevenLog->profit}\n";
echo "profitLog profit: {$profitLog->profit}\n";
