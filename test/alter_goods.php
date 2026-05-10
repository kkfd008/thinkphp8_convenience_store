<?php
$db = new PDO('sqlite:database/shop.db');
try {
    $db->exec("ALTER TABLE goods ADD COLUMN unit VARCHAR(20) DEFAULT ''");
    echo "Column 'unit' added to goods table\n";
} catch (PDOException $e) {
    echo "Note: " . $e->getMessage() . "\n";
}
