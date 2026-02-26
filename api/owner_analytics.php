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

try {
    $analytics = [];

    // 1. Revenue by Branch (Bar Chart)
    $query = "SELECT b.name, COALESCE(SUM(s.total_amount), 0) as revenue 
              FROM branches b
              LEFT JOIN sales s ON b.id = s.branch_id AND s.status != 'returned'
              WHERE s.status != 'returned' OR s.status IS NULL
              GROUP BY b.id";
    $stmt = $db->query($query);
    $analytics['revenue_by_branch'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Monthly Revenue Trend (Line Chart - Last 6 months)
    $query = "SELECT DATE_FORMAT(sale_date, '%b %Y') as month, SUM(total_amount) as revenue
              FROM sales
              WHERE sale_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
              AND status != 'returned'
              GROUP BY month
              ORDER BY sale_date ASC";
    $stmt = $db->query($query);
    $analytics['monthly_trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Sales by Category (Pie Chart)
    $query = "SELECT c.name, SUM(si.quantity) as count
              FROM sale_items si
              JOIN products p ON si.product_id = p.id
              JOIN categories c ON p.category_id = c.id
              JOIN sales s ON si.sale_id = s.id
              WHERE s.status != 'returned'
              GROUP BY c.id";
    $stmt = $db->query($query);
    $analytics['sales_by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Top 10 Products Company-Wide
    $query = "SELECT p.model as name, SUM(si.quantity) as value
              FROM sale_items si
              JOIN products p ON si.product_id = p.id
              JOIN sales s ON si.sale_id = s.id
              WHERE s.status != 'returned'
              GROUP BY p.id
              ORDER BY value DESC
              LIMIT 10";
    $stmt = $db->query($query);
    $analytics['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $analytics]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
