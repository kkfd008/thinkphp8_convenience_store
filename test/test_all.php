<?php
$base = 'http://localhost:8015';
$pass = 0; $fail = 0;

function httpDo($method, $url, $data, $cookie) {
    $opts = ['http' => [
        'method' => $method,
        'header' => "Content-Type: application/x-www-form-urlencoded\r\nCookie: $cookie",
        'timeout' => 10, 'ignore_errors' => true
    ]];
    if ($data) $opts['http']['content'] = http_build_query($data);
    $ctx = stream_context_create($opts);
    $res = @file_get_contents($url, false, $ctx);
    $newCookie = '';
    if (isset($http_response_header))
        foreach ($http_response_header as $h)
            if (stripos($h, 'Set-Cookie:') !== false)
                $newCookie = trim(explode(';', substr($h, 12))[0]);
    return [$res, $newCookie ?: $cookie];
}

function httpPost($url, $data, $cookie) { return httpDo('POST', $url, $data, $cookie); }
function httpGet($url, $cookie) { return httpDo('GET', $url, null, $cookie); }

function test($label, $ok) { global $pass, $fail;
    echo ($ok ? "  ✅" : "  ❌") . " $label\n";
    if ($ok) $pass++; else $fail++;
}

// === 1. Login ===
echo "=== 1. Login ===\n";
list($res, $cookie) = httpPost("$base/login/doLogin", ['username'=>'admin','password'=>'admin123'], '');
$j = json_decode($res, true);
test('Login', $j && ($j['code']??1)===0 && $cookie !== '');

// === 2. Page Access ===
echo "\n=== 2. Page Access ===\n";
$pages = [
    ['/index/index', 'Dashboard', '便利店'],
    ['/supplier/index', 'Supplier', '新增供货商'],
    ['/goods/index', 'Goods', '新增商品'],
    ['/stock/index', 'Stock', '库存'],
    ['/stock/warning', 'StockWarning', '预警'],
    ['/cashier/index', 'Cashier', '购物车'],
    ['/order/index', 'Order', '订单号'],
    ['/member/index', 'Member', '新增会员'],
    ['/member/cateList', 'MemberCate', '新增分类'],
    ['/member/recharge', 'Recharge', '会员充值'],
    ['/member/rechargeLog', 'RechargeLog', '充值'],
    ['/auth/ruleList', 'AuthRule', '新增顶级菜单'],
    ['/auth/roleList', 'AuthRole', '新增角色'],
    ['/auth/adminList', 'AuthAdmin', '新增管理员'],
];
foreach ($pages as [$url, $label, $keyword]) {
    list($html, $_) = httpGet($base . $url, $cookie);
    test("$label", $html !== false && strpos($html, $keyword) !== false);
}

// === 3. Supplier CRUD ===
echo "\n=== 3. Supplier CRUD ===\n";
list($res, $cookie) = httpPost("$base/supplier/add", ['name'=>'测试供应商133','contact'=>'张三','phone'=>'13312345678','address'=>'上海','remark'=>'','status'=>1], $cookie);
$j = json_decode($res, true);
test('Supplier add', $j && ($j['code']??1)===0);
if (!$j || ($j['code']??1)!==0) echo "     (msg: " . ($j['msg']??'error') . ")\n";

echo "\nSearch \"133\":\n";
list($html, $_) = httpGet($base . '/supplier/index?keyword=133', $cookie);
$found = $html !== false && strpos($html, '测试供应商133') !== false;
test('Search 133', $found);
if (!$found) {
    echo "  Retrieved " . ($html !== false ? strlen($html) : 0) . " bytes\n";
    if ($html !== false) {
        // Check all scripts for data
        if (preg_match('/var supplierList\s*=\s*(\[.+?\]);/s', $html, $m)) {
            echo "  supplierList: " . substr($m[1], 0, 500) . "\n";
        }
    }
}

list($html, $_) = httpGet($base . '/supplier/index?keyword=13312345678', $cookie);
test('Search phone', $html !== false && strpos($html, '测试供应商133') !== false);

// Toggle
list($res, $cookie) = httpPost("$base/supplier/toggleStatus", ['id'=>1,'status'=>0], $cookie);
$j = json_decode($res, true);
test('ToggleStatus', $j && ($j['code']??1)===0);

// Edit
list($res, $cookie) = httpPost("$base/supplier/edit", ['id'=>1,'name'=>'改名供应商','contact'=>'李四','phone'=>'13800000000','address'=>'北京','remark'=>'test','status'=>1], $cookie);
$j = json_decode($res, true);
test('Supplier edit', $j && ($j['code']??1)===0);

// Delete
list($res, $cookie) = httpPost("$base/supplier/delete", ['id'=>1], $cookie);
$j = json_decode($res, true);
test('Supplier delete', $j && ($j['code']??1)===0);

// === 4. Goods CRUD ===
echo "\n=== 4. Goods CRUD ===\n";
list($res, $cookie) = httpPost("$base/goods/add", ['name'=>'测试商品','barcode'=>'1234567890123','purchase_price'=>'5.00','retail_price'=>'10.00','cate'=>'饮料','stock_min'=>'10','stock_max'=>'100'], $cookie);
$j = json_decode($res, true);
test('Goods add', $j && ($j['code']??1)===0);

list($res, $cookie) = httpPost("$base/goods/genBarcode", [], $cookie);
$j = json_decode($res, true);
test('genBarcode', $j && ($j['code']??1)===0 && strlen($j['data']['barcode']??'')===13);

list($redis, $cookie) = httpPost("$base/goods/edit", ['id'=>1,'name'=>'测试商品2','barcode'=>'1234567890123','purchase_price'=>'6.00','retail_price'=>'15.00','cate'=>'饮料','stock_min'=>'5','stock_max'=>'50'], $cookie);
$j = json_decode($redis, true);
test('Goods edit', $j && ($j['code']??1)===0);

list($html, $_) = httpGet($base . '/goods/index?keyword=测试', $cookie);
test('Goods search', $html !== false && strpos($html, '测试商品2') !== false);

// === 5. Add supplier for purchase ===
echo "\n=== 5. Purchase ===\n";
list($res, $cookie) = httpPost("$base/supplier/add", ['name'=>'进货供应商','contact'=>'','phone'=>'','address'=>'','remark'=>'','status'=>1], $cookie);
$j = json_decode($res, true);
$supplierId = $j['data']['id'] ?? 2; 
test('Supplier add2', $j && ($j['code']??1)===0);

$items = json_encode([['barcode'=>'1234567890123','goods_name'=>'测试商品2','purchase_price'=>'5.00','retail_price'=>'15.00','box_spec'=>10,'box_count'=>5,'piece_count'=>3]]);
list($res, $cookie) = httpPost("$base/purchase/doAdd", ['supplier_id'=>$supplierId,'items'=>$items,'remark'=>''], $cookie);
$j = json_decode($res, true);
test('Purchase add', $j && ($j['code']??1)===0);

// Check stock after purchase: 10*5+3 = 53
list($res, $cookie) = httpGet($base . '/cashier/searchGoods?keyword=1234567890123', $cookie);
$goods = json_decode($res, true);
$stock = $goods['data'][0]['stock'] ?? 0;
test("Stock=$stock (expect 53)", $stock == 53);

// === 6. Cashier ===
echo "\n=== 6. Cashier ===\n";
list($res, $cookie) = httpGet($base . '/cashier/searchGoods?keyword=1234567890123', $cookie);
test('searchGoods', json_decode($res, true)['code']??1 === 0);

$items = json_encode([['barcode'=>'1234567890123','quantity'=>2]]);
list($res, $cookie) = httpPost("$base/cashier/doCheckout", ['items'=>$items,'pay_type'=>1,'member_id'=>0], $cookie);
$j = json_decode($res, true);
test('Checkout(cash)', $j && ($j['code']??1)===0);

// === 7. Order ===
echo "\n=== 7. Order ===\n";
list($html, $_) = httpGet($base . '/order/index', $cookie);
test('Order list', $html !== false && strpos($html, 'DD') !== false);

list($html, $_) = httpGet($base . '/order/detail?id=1', $cookie);
test('Order detail', $html !== false && strpos($html, '订单明细') !== false);

// === 8. Member ===
echo "\n=== 8. Member ===\n";
list($res, $cookie) = httpPost("$base/member/cateAdd", ['name'=>'VIP','discount'=>'0.95'], $cookie);
$j = json_decode($res, true);
test('Cate add', $j && ($j['code']??1)===0);

list($res, $cookie) = httpPost("$base/member/add", ['name'=>'测试会员','phone'=>'13900000001','cate_id'=>1,'remark'=>''], $cookie);
test('Member add', json_decode($res, true)['code']??1 === 0);

list($res, $cookie) = httpPost("$base/member/doRecharge", ['member_id'=>1,'amount'=>500], $cookie);
test('Recharge', json_decode($res, true)['code']??1 === 0);

list($html, $_) = httpGet($base . '/member/index?keyword=139', $cookie);
test('Member search', $html !== false && strpos($html, '测试会员') !== false);

// Check balance
list($html, $_) = httpGet($base . '/member/rechargeLog?member_id=1', $cookie);
test('RechargeLog', $html !== false && strpos($html, '500') !== false);

// === 9. Member checkout ===
echo "\n=== 9. Member checkout ===\n";
$items = json_encode([['barcode'=>'1234567890123','quantity'=>1]]);
list($res, $cookie) = httpPost("$base/cashier/doCheckout", ['items'=>$items,'pay_type'=>2,'member_id'=>1], $cookie);
$j = json_decode($res, true);
test('Member pay', $j && ($j['code']??1)===0);

// Check member balance after
list($html, $_) = httpGet($base . '/member/index?keyword=13900000001', $cookie);
if (preg_match('/var memberList\s*=\s*(\[.+?\]);/s', $html, $m)) {
    $members = json_decode($m[1], true);
    $balance = $members[0]['balance'] ?? -1;
    test("Member balance ($balance)", $balance < 500);
}

// === 10. Auth ===
echo "\n=== 10. Auth ===\n";
list($res, $cookie) = httpPost("$base/auth/ruleAdd", ['pid'=>0,'title'=>'TestMenu','name'=>'Test/index','icon'=>'layui-icon-test','sort'=>99], $cookie);
test('Rule add', json_decode($res, true)['code']??1 === 0);

list($res, $cookie) = httpPost("$base/auth/roleEdit", ['id'=>2,'name'=>'店长','rules'=>'1,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22'], $cookie);
test('Role edit', json_decode($res, true)['code']??1 === 0);

list($res, $cookie) = httpPost("$base/auth/adminAdd", ['username'=>'test','password'=>'123456','role_id'=>2], $cookie);
test('Admin add', json_decode($res, true)['code']??1 === 0);

// === 11. Stock ===
echo "\n=== 11. Stock ===\n";
list($html, $_) = httpGet($base . '/stock/index', $cookie);
test('Stock overview', $html !== false && strpos($html, '1234567890123') !== false);

list($res, $cookie) = httpPost("$base/stock/updateThreshold", ['id'=>1,'stock_min'=>8,'stock_max'=>200], $cookie);
test('Threshold', json_decode($res, true)['code']??1 === 0);

list($html, $_) = httpGet($base . '/stock/warning?type=all', $cookie);
test('Warning page', $html !== false);

// === Summary ===
echo "\n=== Summary: PASS=$pass FAIL=$fail ===\n";
if ($fail > 0) echo "SOME TESTS FAILED!\n"; else echo "ALL TESTS PASSED!\n";
