<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ViewErrorBag;

$user = \App\Models\User::firstOrCreate(
    ['email' => 'admin@transport.app'],
    ['name' => 'Admin', 'password' => bcrypt('password'), 'is_active' => true, 'is_super_admin' => true]
);
Auth::login($user);

view()->share('errors', new ViewErrorBag());

// Use the same data as AccountController::create()
$branches = \App\Models\Branch::all();
$drivers = \App\Models\Driver::all();
$fuelStations = \App\Models\FuelStation::all();

$blade = view('accounts.create', compact('branches', 'drivers', 'fuelStations'))->render();

file_put_contents(__DIR__ . '/debug_output.html', $blade);

// Find the creatable-select x-data attribute
$pos = strpos($blade, 'creatableSelect');
if ($pos !== false) {
    echo "Found 'creatableSelect' at position $pos" . PHP_EOL;
    echo PHP_EOL . "Context (800 chars from position):" . PHP_EOL;
    echo substr($blade, $pos - 50, 800) . PHP_EOL;
} else {
    echo "ERROR: 'creatableSelect' NOT found in the rendered HTML!" . PHP_EOL;
}

// Also check for x-show on dropdown
$pos2 = strpos($blade, 'x-show="open"');
if ($pos2 !== false) {
    echo PHP_EOL . "Found x-show=\"open\" at position $pos2" . PHP_EOL;
} else {
    echo PHP_EOL . "ERROR: x-show=\"open\" not found!" . PHP_EOL;
}

// Check for fixed positioning
if (strpos($blade, 'fixed z') !== false) {
    echo PHP_EOL . "Found 'fixed z' (fixed positioning with z-index)" . PHP_EOL;
} else {
    echo PHP_EOL . "ERROR: 'fixed z' not found - dropdown may not have fixed positioning!" . PHP_EOL;
}

// Check for :style binding
if (strpos($blade, 'dropdownTop') !== false) {
    echo PHP_EOL . "Found 'dropdownTop' style binding" . PHP_EOL;
} else {
    echo PHP_EOL . "ERROR: 'dropdownTop' not found!" . PHP_EOL;
}
