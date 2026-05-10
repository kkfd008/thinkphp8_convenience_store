<?php
require 'vendor/autoload.php';

// Simulate what importSheets does
$file = 'jhd/hp.xlsx';
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
$spreadsheet = $reader->load($file);
$sheetCount = $spreadsheet->getSheetCount();

for ($s = 0; $s < $sheetCount; $s++) {
    $sheet = $spreadsheet->getSheet($s);
    $rows = $sheet->toArray();
    $sheetName = $spreadsheet->getSheetNames()[$s];
    if (count($rows) < 4) continue;

    // Find header row and date
    $headerRow = -1;
    $purchaseDate = '';
    $colOffset = 0;

    for ($r = 0; $r < min(count($rows), 5); $r++) {
        $rowText = implode('', array_map(function($v){ return (string)$v; }, $rows[$r]));
        if (strpos($rowText, '日期') !== false) {
            foreach ($rows[$r] as $cell) {
                if (is_string($cell) && preg_match('/(\d{4})年(\d{1,2})月(\d{1,2})/', $cell, $m)) {
                    $purchaseDate = sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
                }
            }
        }
        if (strpos($rowText, '货号') !== false && strpos($rowText, '商品名称') !== false) {
            $headerRow = $r;
            if (empty($rows[$r][0]) && !empty($rows[$r][1]) && strpos((string)$rows[$r][1], '行号') !== false) {
                $colOffset = 1;
            }
        }
    }

    echo "\nSheet '$sheetName': headerRow=$headerRow, date=$purchaseDate, offset=$colOffset\n";

    $itemCount = 0;
    for ($r = $headerRow + 1; $r < count($rows); $r++) {
        $row = $rows[$r];
        $barcode = trim((string)($row[$colOffset + 1] ?? ''));
        $name    = trim((string)($row[$colOffset + 2] ?? ''));

        if (empty($barcode)) break;
        if (strpos($name, '金额小计') !== false || strpos($name, '合计') !== false) break;
        if (strpos($barcode, '核准人') !== false) break;

        $boxSpec  = intval($row[$colOffset + 3] ?? 0);
        $boxCount = floatval($row[$colOffset + 4] ?? 0);
        $totalQty = intval($row[$colOffset + 5] ?? 0);
        $unit     = trim((string)($row[$colOffset + 6] ?? ''));
        $purchasePrice = floatval($row[$colOffset + 7] ?? 0);
        $retailPrice   = floatval($row[$colOffset + 9] ?? 0);

        $boxCountInt = intval($boxCount);
        $pieceCount  = $totalQty - $boxSpec * $boxCountInt;
        if ($pieceCount < 0) $pieceCount = 0;

        $itemCount++;
        if ($itemCount <= 3 || $r == count($rows) - 1) {
            echo "  $barcode | $name | 箱规=$boxSpec 箱数=$boxCount($boxCountInt) 数量=$totalQty 散件=$pieceCount | 进价=$purchasePrice\n";
        }
    }
    echo "  Total: $itemCount items\n";
}
echo "\nALL DONE\n";
