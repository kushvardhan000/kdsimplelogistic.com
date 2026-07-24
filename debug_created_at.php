<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$log = App\Models\TransportLog::factory()->create(['created_at' => '2025-03-15 10:00:00']);
echo "created_at: {$log->created_at}\n";
