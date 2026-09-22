<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$results = \Illuminate\Support\Facades\DB::table('migrations')->where('migration', 'like', '%file_path%')->get();
foreach($results as $r) {
    echo $r->migration . PHP_EOL;
}

$results2 = \Illuminate\Support\Facades\DB::table('migrations')->where('migration', 'like', '%logsheet%')->get();
foreach($results2 as $r) {
    echo $r->migration . " | batch: " . $r->batch . PHP_EOL;
}