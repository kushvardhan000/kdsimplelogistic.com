<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$log = App\Models\TransportLog::factory()->create();
echo "ID: {$log->id}\n";
echo "Before update - to_bb_sale: {$log->to_bb_sale}, freight: {$log->freight}, profit: {$log->profit}\n";

$log->update([
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

$log->refresh();
echo "After update - to_bb_sale: {$log->to_bb_sale}, freight: {$log->freight}, profit: {$log->profit}\n";
