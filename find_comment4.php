<?php
require 'vendor/autoload.php';

$ref = new ReflectionClass('PhpOffice\PhpSpreadsheet\Worksheet\Worksheet');
foreach ($ref->getMethods() as $m) {
    if (stripos($m->getName(), 'comment') !== false) {
        echo $m->getName() . PHP_EOL;
        echo '  params: ';
        foreach ($m->getParameters() as $p) {
            echo $p->getName() . ' ';
        }
        echo PHP_EOL;
    }
}
