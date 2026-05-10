<?php
$fixes = [
    'view/auth/admin_list.html' => ['admins', 'roles'],
    'view/auth/role_list.html' => ['roles', 'ruleTree'],
    'view/auth/rule_list.html' => ['rules'],
    'view/goods/cate_list.html' => ['cateList'],
    'view/goods/index.html' => ['list'],
    'view/index/index.html' => ['trend_dates', 'trend_sales', 'trend_purchases', 'top10_names', 'top10_counts'],
    'view/layout.html' => ['admin', 'menus'],
    'view/member/cate_list.html' => ['list'],
    'view/member/index.html' => ['list', 'cates'],
    'view/member/recharge.html' => ['members'],
    'view/member/recharge_log.html' => ['list'],
    'view/order/index.html' => ['list'],
    'view/purchase/index.html' => ['list'],
    'view/stock/index.html' => ['list'],
    'view/stock/warning.html' => ['list'],
    'view/supplier/index.html' => ['list'],
];

$count = 0;
foreach ($fixes as $file => $vars) {
    $c = file_get_contents($file);
    foreach ($vars as $var) {
        $old = '{:json_encode($1, JSON_UNESCAPED_UNICODE)}';
        $new = '{:json_encode($' . $var . ', JSON_UNESCAPED_UNICODE)}';
        $pos = strpos($c, $old);
        if ($pos !== false) {
            $c = substr_replace($c, $new, $pos, strlen($old));
            echo "FIX($var): $file\n";
            $count++;
        }
        // Also try with escaped $
        $old2 = '{:json_encode(\$1, JSON_UNESCAPED_UNICODE)}';
        $pos2 = strpos($c, $old2);
        if ($pos2 !== false) {
            $c = substr_replace($c, $new, $pos2, strlen($old2));
            echo "FIX2($var): $file\n";
            $count++;
        }
    }
    file_put_contents($file, $c);
}
echo "Fixed $count occurrences.\n";
