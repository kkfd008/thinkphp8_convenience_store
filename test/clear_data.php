<?php
$dbPath = __DIR__ . '/database/shop.db';
if (!file_exists($dbPath)) { echo "数据库不存在\n"; exit(1); }

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "清空前数据统计：\n";
echo "  order_detail: " . $db->query("SELECT COUNT(*) FROM order_detail")->fetchColumn() . " 条\n";
echo "  order: " . $db->query("SELECT COUNT(*) FROM [order]")->fetchColumn() . " 条\n";
echo "  purchase_detail: " . $db->query("SELECT COUNT(*) FROM purchase_detail")->fetchColumn() . " 条\n";
echo "  purchase: " . $db->query("SELECT COUNT(*) FROM purchase")->fetchColumn() . " 条\n";
echo "  goods: " . $db->query("SELECT COUNT(*) FROM goods")->fetchColumn() . " 条\n";

echo "\n开始清空...\n";

$db->exec("DELETE FROM order_detail");
echo "  ✓ order_detail 已清空\n";

$db->exec("DELETE FROM [order]");
echo "  ✓ order 已清空\n";

$db->exec("DELETE FROM purchase_detail");
echo "  ✓ purchase_detail 已清空\n";

$db->exec("DELETE FROM purchase");
echo "  ✓ purchase 已清空\n";

$db->exec("DELETE FROM goods");
echo "  ✓ goods 已清空\n";

echo "\n清空后数据统计：\n";
echo "  order_detail: " . $db->query("SELECT COUNT(*) FROM order_detail")->fetchColumn() . " 条\n";
echo "  order: " . $db->query("SELECT COUNT(*) FROM [order]")->fetchColumn() . " 条\n";
echo "  purchase_detail: " . $db->query("SELECT COUNT(*) FROM purchase_detail")->fetchColumn() . " 条\n";
echo "  purchase: " . $db->query("SELECT COUNT(*) FROM purchase")->fetchColumn() . " 条\n";
echo "  goods: " . $db->query("SELECT COUNT(*) FROM goods")->fetchColumn() . " 条\n";

echo "\n清空完成！\n";
