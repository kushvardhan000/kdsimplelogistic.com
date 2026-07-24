<?php
require 'vendor/autoload.php';

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'test');
$comment = $sheet->getComment('A1');
echo get_class($comment) . PHP_EOL;
if ($comment) {
    $methods = get_class_methods($comment);
    foreach ($methods as $m) {
        echo $m . PHP_EOL;
    }
}
