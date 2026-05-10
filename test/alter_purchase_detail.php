<?php
$db = new PDO('sqlite:database/shop.db');
try {
    $db->exec("ALTER TABLE purchase_detail ADD COLUMN unit VARCHAR(20) DEFAULT ''");
    echo "Column 'unit' added to purchase_detail\n";
} catch (PDOException $e) {
    echo "Note: " . $e->getMessage() . "\n";
}
