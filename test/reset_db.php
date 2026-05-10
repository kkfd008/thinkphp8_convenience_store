<?php
unlink('database/shop.db');
$db = new PDO('sqlite:database/shop.db');
$db->exec(file_get_contents('database/schema.sql'));
echo "DB reset OK\n";
