<?php
$controllers = ['Supplier.php', 'Goods.php', 'Purchase.php', 'Stock.php', 'Order.php', 'Member.php'];
foreach ($controllers as $file) {
    $path = 'app/controller/' . $file;
    $c = file_get_contents($path);
    $c = str_replace(
        "\$sheet->setCellValueByColumnAndRow(\$i + 1, 1, \$h)",
        "\$sheet->setCellValue([\$i + 1, 1], \$h)",
        $c
    );
    $c = str_replace(
        "\$sheet->setCellValueByColumnAndRow(\$ci + 1, \$ri + 2, \$val)",
        "\$sheet->setCellValue([\$ci + 1, \$ri + 2], \$val)",
        $c
    );
    file_put_contents($path, $c);
    echo "OK: $file\n";
}
echo "Done.\n";
