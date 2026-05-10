<?php
$db = new PDO('sqlite:' . __DIR__ . '/database/shop.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cols = $db->query("PRAGMA table_info(goods_cate)")->fetchAll(PDO::FETCH_ASSOC);
$hasStatus = false;
foreach ($cols as $c) { if ($c['name'] === 'status') $hasStatus = true; }

if (!$hasStatus) {
    $db->exec("ALTER TABLE goods_cate ADD COLUMN status TINYINT DEFAULT 1");
    $db->exec("UPDATE goods_cate SET status = 1");
    echo "status column added\n";
} else {
    echo "status column already exists\n";
}

$cates = $db->query("SELECT id, name, status FROM goods_cate")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cates as $c) {
    echo "  [{$c['id']}] {$c['name']} status={$c['status']}\n";
}
