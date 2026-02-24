<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

echo '=== Check Column Lengths ===' . "\n";

$query = "SELECT COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH 
          FROM INFORMATION_SCHEMA.COLUMNS 
          WHERE TABLE_SCHEMA = 'ibs_store' 
          AND TABLE_NAME = 'product_items' 
          AND COLUMN_NAME IN ('imei', 'serial_number', 'item_code')";

$stmt = $db->prepare($query);
$stmt->execute();

echo "Column | Max Length\n";
echo "----------------\n";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['COLUMN_NAME'] . " | " . $row['CHARACTER_MAXIMUM_LENGTH'] . "\n";
}

echo "\n=== Recent Product Items ===\n";
$query2 = "SELECT product_id, item_code, imei, serial_number 
           FROM product_items 
           WHERE product_id >= 30 
           ORDER BY product_id, id 
           LIMIT 10";

$stmt2 = $db->prepare($query2);
$stmt2->execute();

while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['product_id'] . 
         " | Code: " . $row['item_code'] . 
         " | IMEI: " . ($row['imei'] ?? 'NULL') . 
         " | Serial: " . ($row['serial_number'] ?? 'NULL') . "\n";
}
?>
