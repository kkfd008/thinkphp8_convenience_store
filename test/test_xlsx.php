<?php
require 'vendor/autoload.php';

$file = 'jhd/hp.xlsx';
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
$spreadsheet = $reader->load($file);

$sheetNames = $spreadsheet->getSheetNames();
echo "Sheet count: " . count($sheetNames) . "\n";
foreach ($sheetNames as $i => $name) {
    echo "\n=== Sheet $i: '$name' ===\n";
    $sheet = $spreadsheet->getSheet($i);
    $rows = $sheet->toArray();
    echo "Rows: " . count($rows) . "\n";
    for ($r = 0; $r < min(count($rows), 8); $r++) {
        echo "  Row $r: " . json_encode(array_slice($rows[$r], 0, 15), JSON_UNESCAPED_UNICODE) . "\n";
    }
    // Dump columns from a random row to see full width
    if (count($rows) > 5) {
        $mid = intval(count($rows) / 2);
        echo "  Row $mid: " . json_encode($rows[$mid], JSON_UNESCAPED_UNICODE) . "\n";
    }
}
