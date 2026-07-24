<?php
require 'vendor/autoload.php';

$found = false;
foreach (get_declared_classes() as $c) {
    if (str_contains($c, 'Comment')) {
        echo $c . PHP_EOL;
        $found = true;
    }
}

if (!$found) {
    echo "No Comment class found in declared classes\n";
}

// Also check if RichText comment exists
foreach (get_declared_classes() as $c) {
    if (str_contains($c, 'RichText')) {
        echo $c . PHP_EOL;
    }
}
