<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$reader = IOFactory::createReaderForFile('JUNE LOGDATE.xlsx');
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load('JUNE LOGDATE.xlsx');
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray(null, false, false, false);

$headers = $rows[0] ?? [];
$data = $rows[1] ?? [];

var_dump($headers);
var_dump($data);
