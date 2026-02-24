<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

echo '=== Fix Stock Discrepancies ===' . "\n";

// Update products table to match actual product_items count
$updateQuery = "UPDATE products p 
                SET p.quantity = (
                    SELECT COUNT(*) 
                    FROM product_items pi 
                    WHERE pi.product_id = p.id AND pi.status = 'available'
                )
                WHERE p.id IN (25, 26, 27, 28)";

$stmt = $db->prepare($updateQuery);
if ($stmt->execute()) {
    echo "Updated products table to match actual available stock\n";
}

// Show the updated values
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

echo "\n=== Updated Stock Values ===\n";
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
?>
