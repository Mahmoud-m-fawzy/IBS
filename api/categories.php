<?php
// Categories API
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit();
}

try {
    include_once '../config/database.php';

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'POST':
            $data = json_decode(file_get_contents("php://input"));
            if (!empty($data->name)) {
                $query = "INSERT INTO categories (name, description) VALUES (?, ?)";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$data->name, $data->description ?? ''])) {
                    echo json_encode(['success' => true, 'message' => 'Category added', 'id' => $db->lastInsertId()]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add category']);
                }
            }
            break;

        case 'PUT':
            $data = json_decode(file_get_contents("php://input"));
            if (!empty($data->id) && !empty($data->name)) {
                $query = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$data->name, $data->description ?? '', $data->id])) {
                    echo json_encode(['success' => true, 'message' => 'Category updated']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update category']);
                }
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                $query = "UPDATE categories SET is_active = 0 WHERE id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$_GET['id']])) {
                    echo json_encode(['success' => true, 'message' => 'Category deleted']);
                }
            }
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            break;
    }

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

ob_end_flush();
?>
