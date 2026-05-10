<?php
require 'vendor/autoload.php';
$ss = new PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $ss->getActiveSheet();
$methods = get_class_methods($sheet);
$relevant = array_filter($methods, function($m) { return stripos($m, 'cell') !== false || stripos($m, 'column') !== false; });
sort($relevant);
foreach ($relevant as $m) echo "  $m\n";
