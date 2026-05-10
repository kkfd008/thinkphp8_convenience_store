<?php
$base = 'http://localhost:8008';

function httpPost($url, $data, &$cookie) {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded",
            'content' => http_build_query($data),
            'timeout' => 10,
            'ignore_errors' => true,
        ]
    ]);
    $res = @file_get_contents($url, false, $ctx);
    if (isset($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (stripos($h, 'Set-Cookie:') !== false) {
                $cookie = trim(explode(';', substr($h, 12))[0]);
            }
        }
    }
    return $res;
}

function httpGet($url, $cookie) {
    $ctx = stream_context_create([
        'http' => ['header' => 'Cookie: ' . $cookie, 'timeout' => 10, 'ignore_errors' => true]
    ]);
    return @file_get_contents($url, false, $ctx);
}

$cookie = '';
$res = httpPost($base . '/login/doLogin', ['username' => 'admin', 'password' => 'admin123'], $cookie);
$j = json_decode($res, true);
if (!$j || ($j['code']??1) != 0) die("Login FAIL: " . substr($res, 0, 100) . "\n");
echo "1. Login: PASS\n";

if (empty($cookie)) die("No cookie\n");

$tests = [
    ['/index/index', '2. Dashboard', '便利店管理系统'],
    ['/supplier/index', '3. Supplier', '新增供货商'],
    ['/goods/index', '4. Goods', '新增商品'],
    ['/stock/index', '5. Stock', '库存'],
    ['/cashier/index', '6. Cashier', '购物车'],
    ['/order/index', '7. Order', '订单号'],
    ['/member/index', '8. Member', '新增会员'],
    ['/member/cateList', '9. MemberCate', '会员分类'],
    ['/member/recharge', '10. Recharge', '会员充值'],
    ['/member/rechargeLog', '11. RechargeLog', '充值记录'],
    ['/auth/ruleList', '12. AuthRule', '新增顶级菜单'],
    ['/auth/roleList', '13. AuthRole', '新增角色'],
    ['/auth/adminList', '14. AuthAdmin', '新增管理员'],
];

foreach ($tests as [$url, $label, $keyword]) {
    $res = httpGet($base . $url, $cookie);
    $ok = $res !== false && strpos($res, $keyword) !== false;
    echo "$label: " . ($ok ? 'PASS' : 'FAIL (' . ($res !== false ? strlen($res) . 'b' : 'no response') . ')') . "\n";
    if (!$ok && $res !== false) {
        $p = substr(strip_tags($res), 0, 150);
        if (strpos($p, '系统发生错误') !== false) {
            $logFile = 'runtime/log/' . date('Ym') . '/' . date('d') . '.log';
            if (file_exists($logFile)) {
                $lines = file($logFile);
                echo "  Last log: " . end($lines) . "\n";
            }
        }
        echo "  Preview: " . $p . "\n";
    }
}
echo "Done.\n";
