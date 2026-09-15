<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$reader = IOFactory::createReaderForFile('JUNE LOGDATE.xlsx');
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load('JUNE LOGDATE.xlsx');
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray(null, false, false, false);

for ($i = 0; $i < min(6, count($rows)); $i++) {
    echo "ROW {$i}:\n";
    foreach ($rows[$i] as $j => $value) {
        if ($value === null || trim((string)$value) === '') {
            continue;
        }
        echo "  {$j}: " . json_encode($value) . "\n";
    }
}
