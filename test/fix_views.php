<?php
$fixes = [
    'view/member/recharge_log.html' => ['list'],
    'view/member/recharge.html' => ['members'],
    'view/member/cate_list.html' => ['list'],
    'view/member/index.html' => ['list', 'cates'],
    'view/order/index.html' => ['list'],
    'view/stock/warning.html' => ['list'],
    'view/stock/index.html' => ['list'],
    'view/purchase/index.html' => ['list'],
    'view/goods/cate_list.html' => ['cateList'],
    'view/goods/index.html' => ['list'],
    'view/supplier/index.html' => ['list'],
    'view/auth/role_list.html' => ['roles', 'ruleTree'],
    'view/auth/admin_list.html' => ['admins', 'roles'],
    'view/auth/rule_list.html' => ['rules'],
    'view/index/index.html' => ['trend_dates', 'trend_sales', 'trend_purchases', 'top10_names', 'top10_counts'],
];

$count = 0;
foreach ($fixes as $file => $vars) {
    $path = __DIR__ . '/' . $file;
    $content = file_get_contents($path);
    foreach ($vars as $var) {
        $old = '{:json_encode($1)}';
        $new = '{:json_encode($' . $var . ')}';
        $pos = strpos($content, $old);
        if ($pos !== false) {
            $content = substr_replace($content, $new, $pos, strlen($old));
            echo "FIX: $file -> $new\n";
            $count++;
        }
    }
    file_put_contents($path, $content);
}
echo "\nFixed $count occurrences.\n";
