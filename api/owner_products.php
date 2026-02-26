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

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            // Get all products with aggregated stock across branches
            // and branch-specific details if requested
            $query = "SELECT p.*, c.name as category_name, b.name as brand_name, br.name as branch_name
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      LEFT JOIN brands b ON p.brand_id = b.id
                      LEFT JOIN branches br ON p.branch_id = br.id
                      ORDER BY p.id DESC";
            $stmt = $db->query($query);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $products]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->id)) {
            try {
                // Global pricing update or basic data update
                $query = "UPDATE products SET 
                          suggested_price = ?, 
                          purchase_price = ?, 
                          min_selling_price = ?, 
                          min_stock = ?,
                          is_active = ?
                          WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    $data->suggested_price, 
                    $data->purchase_price, 
                    $data->min_selling_price, 
                    $data->min_stock,
                    $data->is_active,
                    $data->id
                ]);
                echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
