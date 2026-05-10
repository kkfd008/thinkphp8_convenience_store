<?php
$base = 'http://localhost:8017';
function httpDo($method, $url, $data, $cookie) {
    $opts = ['http' => ['method' => $method, 'header' => "Content-Type: application/x-www-form-urlencoded\r\nCookie: $cookie", 'timeout' => 10, 'ignore_errors' => true]];
    if ($data) $opts['http']['content'] = http_build_query($data);
    $res = @file_get_contents($url, false, stream_context_create($opts));
    $newCookie = ''; if (isset($http_response_header)) foreach ($http_response_header as $h) if (strpos($h, 'Set-Cookie:') === 0) $newCookie = trim(explode(';', substr($h, 12))[0]);
    return [$res, $newCookie ?: $cookie];
}
list($r, $c) = httpDo('POST', "$base/login/doLogin", ['username'=>'admin','password'=>'admin123'], '');

list($html, $_) = httpDo('GET', "$base/supplier/index?keyword=" . urlencode('张三'), null, $c);
// Check raw HTML for supplier data
if (preg_match('/var supplierList\s*=\s*(\[.+?\]);/s', $html, $m)) {
    $list = json_decode($m[1], true);
    echo "Found " . count($list) . " suppliers\n";
    foreach ($list as $s) echo "  - {$s['name']} / {$s['contact']}\n";
} else {
    echo "No supplierList found\n";
    echo substr($html, 0, 500) . "\n";
}
