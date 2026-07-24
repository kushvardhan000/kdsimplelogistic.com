<?php
require 'vendor/autoload.php';

$ref = new ReflectionClass('PhpOffice\PhpSpreadsheet\Worksheet\Worksheet');
foreach ($ref->getMethods() as $m) {
    if (stripos($m->getName(), 'comment') !== false) {
        echo $m->getName() . PHP_EOL;
    }
}

// Also check the Cell class
echo "\nCell methods:\n";
$ref2 = new ReflectionClass('PhpOffice\PhpSpreadsheet\Cell\Cell');
foreach ($ref2->getMethods() as $m) {
    if (stripos($m->getName(), 'comment') !== false) {
        echo $m->getName() . PHP_EOL;
    }
}
