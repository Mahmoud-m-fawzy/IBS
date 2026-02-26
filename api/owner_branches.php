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
            $query = "SELECT b.*, 
                      COALESCE(SUM(s.total_amount), 0) as total_revenue,
                      COALESCE(SUM(si.quantity), 0) as units_sold
                      FROM branches b
                      LEFT JOIN sales s ON b.id = s.branch_id AND s.status != 'returned'
                      LEFT JOIN sale_items si ON s.id = si.sale_id
                      GROUP BY b.id";
            $stmt = $db->query($query);
            $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $branches]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->name) && !empty($data->location)) {
            try {
                $query = "INSERT INTO branches (name, location, phone, email, address, is_active) VALUES (?, ?, ?, ?, ?, 1)";
                $stmt = $db->prepare($query);
                $stmt->execute([$data->name, $data->location, $data->phone ?? null, $data->email ?? null, $data->address ?? null]);
                echo json_encode(['success' => true, 'message' => 'Branch added successfully', 'id' => $db->lastInsertId()]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Name and location are required']);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->id)) {
            try {
                $query = "UPDATE branches SET name = ?, location = ?, phone = ?, email = ?, address = ?, is_active = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$data->name, $data->location, $data->phone, $data->email, $data->address, $data->is_active, $data->id]);
                echo json_encode(['success' => true, 'message' => 'Branch updated successfully']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Branch ID is required']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
