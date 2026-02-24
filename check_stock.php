<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

echo '=== Stock Discrepancy Check ===' . "\n";

$query = "SELECT p.id, p.brand, p.model, p.quantity as product_quantity, 
                 COUNT(pi.id) as total_items,
                 SUM(CASE WHEN pi.status = 'available' THEN 1 ELSE 0 END) as available_items
          FROM products p 
          LEFT JOIN product_items pi ON p.id = pi.product_id 
          WHERE p.id IN (25, 26, 27, 28)
          GROUP BY p.id, p.brand, p.model, p.quantity
          ORDER BY p.id";
          
$stmt = $db->prepare($query);
$stmt->execute();

echo "ID | Brand Model | Product Qty | Total Items | Available\n";
echo "--------------------------------------------------------\n";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    printf("%-2d | %-12s | %-11d | %-11d | %-8d\n", 
        $row['id'], 
        $row['brand'] . ' ' . $row['model'], 
        $row['product_quantity'], 
        $row['total_items'], 
        $row['available_items']
    );
}

echo "\n=== Individual Product Items ===\n";
$query2 = "SELECT p.id, p.brand, p.model, pi.item_code, pi.status 
           FROM products p 
           LEFT JOIN product_items pi ON p.id = pi.product_id 
           WHERE p.id IN (25, 26, 27, 28)
           ORDER BY p.id, pi.item_code";

$stmt2 = $db->prepare($query2);
$stmt2->execute();

while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . " | " . $row['brand'] . " " . $row['model'] . 
         " | Item: " . ($row['item_code'] ?? 'NULL') . 
         " | Status: " . ($row['status'] ?? 'NULL') . "\n";
}
?>
