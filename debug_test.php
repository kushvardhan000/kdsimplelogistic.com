<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$log = App\Models\TransportLog::factory()->create();
echo "ID: {$log->id}\n";
echo "Profit after create: {$log->profit}\n";

App\Models\TransportLog::where('id', $log->id)->update(['profit' => -3000]);
$log->refresh();
echo "Profit after update: {$log->profit}\n";

$count = App\Models\TransportLog::where('profit', '<', 0)->count();
echo "Filtered count: {$count}\n";
