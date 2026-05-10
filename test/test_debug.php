<?php
$base = 'http://localhost:8011';
function httpPost($url, $data, &$cookie) {
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded", 'content' => http_build_query($data), 'timeout' => 10, 'ignore_errors' => true]]);
    $res = @file_get_contents($url, false, $ctx);
    if (isset($http_response_header))
        foreach ($http_response_header as $h)
            if (stripos($h, 'Set-Cookie:') !== false) $cookie = trim(explode(';', substr($h, 12))[0]);
    return $res;
}
function httpGet($url, $cookie) {
    return @file_get_contents($url, false, stream_context_create(['http' => ['header' => 'Cookie: ' . $cookie, 'timeout' => 10, 'ignore_errors' => true]]));
}
$cookie = '';
$loginRes = httpPost($base . '/login/doLogin', ['username' => 'admin', 'password' => 'admin123'], $cookie);
echo "Login: " . ($loginRes === false ? 'FAIL (no response)' : json_decode($loginRes, true)['msg'] ?? 'ok') . "\n";
$html = httpGet($base . '/index/index', $cookie);
if ($html === false) { echo "Page fetch FAILED\n"; exit; }
echo "Page: " . strlen($html) . " bytes\n";

// Find all &quot; in script contexts
preg_match_all('/<script\b[^>]*>(.*?)<\/script>/s', $html, $matches);
foreach ($matches[1] as $i => $script) {
    if (strpos($script, '&quot;') !== false) {
        echo "Script #$i has &quot; !!!\n";
        $lines = explode("\n", $script);
        foreach ($lines as $li => $line) {
            if (strpos($line, '&quot;') !== false) {
                echo "  L" . ($li+1) . ": " . substr($line, 0, 150) . "\n";
            }
        }
    }
}
if (strpos($html, '&quot;') === false) {
    echo "ALL CLEAR - No &quot; found!\n";
}

// Also check for ADMIN
if (preg_match('/var ADMIN\s*=\s*(.+?);/s', $html, $m)) {
    echo "\nADMIN value: " . substr($m[1], 0, 100) . "\n";
    echo "Has &quot;: " . (strpos($m[1], '&quot;') !== false ? 'YES' : 'NO') . "\n";
}
