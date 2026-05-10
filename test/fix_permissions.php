<?php

$dbPath = __DIR__ . '/database/shop.db';

if (!file_exists($dbPath)) {
    echo "数据库文件 {$dbPath} 不存在\n";
    exit(1);
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $roles = $db->query("SELECT id, name, rules FROM role")->fetchAll(PDO::FETCH_ASSOC);
    echo "当前角色权限：\n";
    foreach ($roles as $r) {
        echo "  ID={$r['id']} {$r['name']}: rules='{$r['rules']}'\n";
    }

    $db->exec("UPDATE role SET rules = '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22' WHERE id = 1");
    echo "\n✓ 超级管理员 (id=1) 权限已更新\n";

    $db->exec("UPDATE role SET rules = '1,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22' WHERE id = 2");
    echo "✓ 店长 (id=2) 权限已更新：所有业务模块（不含权限管理）\n";

    $db->exec("UPDATE role SET rules = '1,16,17' WHERE id = 3");
    echo "✓ 收银员 (id=3) 权限已更新：首页 + 收银台 + 订单管理\n";

    $roles = $db->query("SELECT id, name, rules FROM role")->fetchAll(PDO::FETCH_ASSOC);
    echo "\n修复后角色权限：\n";
    foreach ($roles as $r) {
        echo "  ID={$r['id']} {$r['name']}: rules='{$r['rules']}'\n";
    }

    echo "\n权限修复完成！\n";

} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}
