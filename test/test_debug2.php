<?php
$base = 'http://localhost:8017';

function httpDo($method, $url, $data, $cookie) {
    $opts = ['http' => ['method' => $method, 'header' => "Content-Type: application/x-www-form-urlencoded\r\nCookie: $cookie", 'timeout' => 10, 'ignore_errors' => true]];
    if ($data) $opts['http']['content'] = http_build_query($data);
    $res = @file_get_contents($url, false, stream_context_create($opts));
    $newCookie = '';
    if (isset($http_response_header))
        foreach ($http_response_header as $h)
            if (stripos($h, 'Set-Cookie:') !== false)
                $newCookie = trim(explode(';', substr($h, 12))[0]);
    return [$res, $newCookie ?: $cookie];
}

// Login
$cookie = '';
list($res, $cookie) = httpDo('POST', "$base/login/doLogin", ['username'=>'admin','password'=>'admin123'], '');
echo "Cookie obtained: " . ($cookie ? 'YES' : 'NO') . "\n";

// Add supplier with 133
list($res, $_) = httpDo('POST', "$base/supplier/add", ['name'=>'测试供应商133ABC','contact'=>'张三','phone'=>'13312345678','address'=>'上海','remark'=>'','status'=>1], $cookie);
echo "Add: " . json_decode($res, true)['msg'] . "\n";

// Search "133"
list($html, $_) = httpDo('GET', "$base/supplier/index?keyword=133", null, $cookie);
$found = strpos($html, '测试供应商133ABC') !== false;
echo "Search 133: " . ($found ? '✅ FOUND' : '❌ NOT FOUND') . "\n";
if (!$found) {
    if (preg_match('/var supplierList\s*=\s*(.+?);/s', $html, $m))
        echo "  supplierList: " . $m[1] . "\n";
}

// Search "张三"
list($html, $_) = httpDo('GET', "$base/supplier/index?keyword=" . urlencode('张三'), null, $cookie);
echo "Search 张三: " . (strpos($html, '测试供应商133ABC') !== false ? '✅ FOUND' : '❌ NOT FOUND') . "\n";

// Search phone
list($html, $_) = httpDo('GET', "$base/supplier/index?keyword=13312345678", null, $cookie);
echo "Search phone: " . (strpos($html, '测试供应商133ABC') !== false ? '✅ FOUND' : '❌ NOT FOUND') . "\n";
