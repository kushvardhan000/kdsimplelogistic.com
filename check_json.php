<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Js;

$config = [
    'name' => 'fuel_station_id',
    'options' => [['value' => 1, 'label' => 'Test Station', 'branch' => 'DEL', 'balance' => 100]],
    'selected' => null,
    'searchable' => true,
    'allowClear' => true,
    'createUrl' => 'http://localhost/entities/fuel-stations/store',
    'fallbackUrl' => 'http://localhost/accounts/create?type=fuel_station',
];

echo "Js::from output:\n";
echo Js::from($config)->toHtml();
echo "\n\njson_encode with flags 15:\n";
echo json_encode($config, 15, 512);
echo "\n\njson_encode without flags:\n";
echo json_encode($config);
echo "\n";
