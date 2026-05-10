<?php
$files = glob('view/*/*.html');
$fixed = 0;
foreach ($files as $f) {
    $c = file_get_contents($f);
    $orig = $c;
    // Fix any $(layero).find or $(index).find -> layui.$(layero).find
    $c = preg_replace('/(layui\.)*\$\((layero|index)\)\.find/', 'layui.$(layero).find', $c);
    if ($c !== $orig) {
        file_put_contents($f, $c);
        $fixed++;
        echo "FIX: $f\n";
    }
}
echo "Fixed $fixed files.\n";
