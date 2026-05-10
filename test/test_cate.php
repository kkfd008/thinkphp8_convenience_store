<?php
$base = 'http://localhost:8019';

function httpDo($method, $url, $data, $cookie) {
    $opts = ['http' => ['method' => $method, 'header' => "Content-Type: application/x-www-form-urlencoded\r\nCookie: $cookie", 'timeout' => 10, 'ignore_errors' => true]];
    if ($data) $opts['http']['content'] = http_build_query($data);
    $res = @file_get_contents($url, false, stream_context_create($opts));
    $newCookie = ''; if (isset($http_response_header)) foreach ($http_response_header as $h) if (strpos($h, 'Set-Cookie:') === 0) $newCookie = trim(explode(';', substr($h, 12))[0]);
    return [$res, $newCookie ?: $cookie];
}

// Login
$ctx = stream_context_create(['http' => ['method'=>'POST','header'=>"Content-Type: application/x-www-form-urlencoded",'content'=>http_build_query(['username'=>'admin','password'=>'admin123']),'timeout'=>10,'ignore_errors'=>true]]);
$res = file_get_contents("$base/login/doLogin", false, $ctx);
$cookie = ''; foreach ($http_response_header as $h) if (strpos($h, 'Set-Cookie:') === 0) $cookie = trim(explode(';', substr($h, 12))[0]);
echo "Cookie: " . ($cookie ?: 'NONE') . "\n";

// Test cateAdd
$ctx = stream_context_create(['http' => ['method'=>'POST','header'=>"Content-Type: application/x-www-form-urlencoded\r\nCookie: $cookie",'content'=>http_build_query(['name'=>'测试分类']),'timeout'=>10,'ignore_errors'=>true]]);
$res = file_get_contents("$base/goods/cateAdd", false, $ctx);
echo "cateAdd: $res\n";

// Test cateList
$ctx = stream_context_create(['http' => ['header'=>'Cookie: '.$cookie,'timeout'=>10,'ignore_errors'=>true]]);
$html = file_get_contents("$base/goods/cateList", false, $ctx);
echo "cateList page: " . strlen($html) . " bytes\n";
echo "Contains '测试分类': " . (strpos($html, '测试分类') !== false ? 'YES' : 'NO') . "\n";
