<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    DB::connection()->getPdo();
    echo "DB_OK\n";
} catch (\Throwable $e) {
    echo "DB_FAIL: " . $e->getMessage() . "\n";
}
