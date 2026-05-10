<?php
$db = new PDO('sqlite:database/shop.db');
$db->exec("CREATE TABLE IF NOT EXISTS goods_cate (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(50) NOT NULL DEFAULT '', create_time INTEGER DEFAULT 0)");
echo "Table goods_cate created.\n";

// Migrate existing categories from goods.cate
$existing = $db->query("SELECT DISTINCT cate FROM goods WHERE cate <> ''")->fetchAll(PDO::FETCH_ASSOC);
$inserted = 0;
foreach ($existing as $row) {
    $name = $row['cate'];
    $check = $db->prepare("SELECT id FROM goods_cate WHERE name = ?");
    $check->execute([$name]);
    if (!$check->fetch()) {
        $db->prepare("INSERT INTO goods_cate (name, create_time) VALUES (?, 0)")->execute([$name]);
        $inserted++;
        echo "  Migrated: $name\n";
    }
}
echo "Migrated $inserted categories.\n";

// Add seed data if table is empty
$count = $db->query("SELECT COUNT(*) FROM goods_cate")->fetchColumn();
if ($count == 0) {
    $seeds = ['饮料', '零食', '日用品', '烟酒', '调味品'];
    foreach ($seeds as $s) {
        $db->prepare("INSERT INTO goods_cate (name, create_time) VALUES (?, 0)")->execute([$s]);
    }
    echo "Seed data inserted.\n";
}
echo "Done.\n";
