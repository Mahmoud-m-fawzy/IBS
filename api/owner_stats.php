<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$branch_id = isset($_GET['branch_id']) && $_GET['branch_id'] !== 'all' ? (int)$_GET['branch_id'] : null;

try {
    $stats = [];
    $where_branch = $branch_id ? "WHERE branch_id = $branch_id" : "";
    $where_branch_sale = $branch_id ? "AND branch_id = $branch_id" : "";

    // 1. Total Branches
    $stmt = $db->query("SELECT COUNT(*) as count FROM branches WHERE is_active = 1");
    $stats['total_branches'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // 2. Total Revenue (This Month)
    $query = "SELECT SUM(total_amount) as total FROM sales 
              WHERE MONTH(sale_date) = MONTH(CURRENT_DATE()) 
              AND YEAR(sale_date) = YEAR(CURRENT_DATE()) 
              AND status != 'returned' $where_branch_sale";
    $stmt = $db->query($query);
    $stats['total_revenue'] = (float)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // 3. Total Units Sold (All Time or This Month? Let's do This Month for consistency)
    $query = "SELECT SUM(si.quantity) as total FROM sale_items si
              JOIN sales s ON si.sale_id = s.id
              WHERE MONTH(s.sale_date) = MONTH(CURRENT_DATE()) 
              AND YEAR(s.sale_date) = YEAR(CURRENT_DATE()) 
              AND s.status != 'returned' $where_branch_sale";
    $stmt = $db->query($query);
    $stats['total_units_sold'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // 4. Net Profit (Revenue - Cost Price)
    $query = "SELECT SUM(si.quantity * (si.unit_price - p.purchase_price)) as profit
              FROM sale_items si
              JOIN sales s ON si.sale_id = s.id
              JOIN products p ON si.product_id = p.id
              WHERE MONTH(s.sale_date) = MONTH(CURRENT_DATE()) 
              AND YEAR(s.sale_date) = YEAR(CURRENT_DATE()) 
              AND s.status != 'returned' $where_branch_sale";
    $stmt = $db->query($query);
    $stats['net_profit'] = (float)($stmt->fetch(PDO::FETCH_ASSOC)['profit'] ?? 0);

    // 5. Total Transactions (This Month)
    $query = "SELECT COUNT(*) as count FROM sales 
              WHERE MONTH(sale_date) = MONTH(CURRENT_DATE()) 
              AND YEAR(sale_date) = YEAR(CURRENT_DATE()) $where_branch_sale";
    $stmt = $db->query($query);
    $stats['total_transactions'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // 6. Low Stock Products
    $low_stock_where = $branch_id ? "AND branch_id = $branch_id" : "";
    $query = "SELECT COUNT(*) as count FROM products WHERE quantity <= min_stock AND is_active = 1 $low_stock_where";
    $stmt = $db->query($query);
    $stats['low_stock_products'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // 7. Total Returns (This Month)
    $query = "SELECT COUNT(*) as count FROM sales 
              WHERE (status = 'returned' OR status = 'partially_returned')
              AND MONTH(sale_date) = MONTH(CURRENT_DATE()) 
              AND YEAR(sale_date) = YEAR(CURRENT_DATE()) $where_branch_sale";
    $stmt = $db->query($query);
    $stats['total_returns'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // 8. Best Performing Branch (by revenue this month)
    $query = "SELECT b.name, SUM(s.total_amount) as revenue 
              FROM sales s
              JOIN branches b ON s.branch_id = b.id
              WHERE MONTH(s.sale_date) = MONTH(CURRENT_DATE()) 
              AND YEAR(s.sale_date) = YEAR(CURRENT_DATE())
              AND s.status != 'returned'
              GROUP BY s.branch_id
              ORDER BY revenue DESC
              LIMIT 1";
    $stmt = $db->query($query);
    $best_branch = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['best_branch'] = $best_branch ? $best_branch['name'] : 'N/A';

    echo json_encode(['success' => true, 'data' => $stats]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
