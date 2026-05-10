<?php
$dbPath = __DIR__ . '/database/shop.db';
if (!file_exists($dbPath)) { echo "DB not found\n"; exit(1); }
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("ALTER TABLE goods ADD COLUMN box_spec INTEGER DEFAULT 0");
echo "box_spec column added to goods table\n";
