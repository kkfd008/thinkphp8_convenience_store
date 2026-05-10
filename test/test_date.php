<?php
require 'vendor/autoload.php';

$file = 'jhd/hp.xlsx';
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
$spreadsheet = $reader->load($file);

foreach ($spreadsheet->getSheetNames() as $s => $name) {
    $sheet = $spreadsheet->getSheet($s);
    $rows = $sheet->toArray();
    $purchaseDate = '';

    for ($r = 0; $r < min(count($rows), 5); $r++) {
        $rowText = implode('', array_map(function($v){ return (string)$v; }, $rows[$r]));
        if (strpos($rowText, '日期') !== false && $purchaseDate === '') {
            for ($ci = 0; $ci < count($rows[$r]) - 1; $ci++) {
                $cv = trim((string)($rows[$r][$ci] ?? ''));
                if (strpos($cv, '日期') !== false) {
                    $nextVal = trim((string)($rows[$r][$ci + 1] ?? ''));
                    if (preg_match('/(\d{4})年(\d{1,2})月(\d{1,2})日/u', $nextVal, $m)) {
                        $purchaseDate = sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
                    }
                }
            }
        }
    }

    echo "Sheet '$name' → date: " . ($purchaseDate ?: 'NOT FOUND') . "\n";
}
