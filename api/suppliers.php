<?php
// Suppliers API
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit();
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal error: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

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
        case 'GET':
            // Get all suppliers or a specific supplier
            if (isset($_GET['id'])) {
                $query = "SELECT * FROM suppliers WHERE id = ? AND is_active = 1";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $_GET['id']);
                $stmt->execute();
                $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($supplier) {
                    echo json_encode([
                        'success' => true,
                        'supplier' => $supplier
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Supplier not found'
                    ]);
                }
            } else {
                $query = "SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name ASC";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $suppliers
                ]);
            }
            break;

        case 'POST':
            // Add new supplier
            $input = file_get_contents("php://input");
            $data = json_decode($input);

            if (empty($data->name)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Supplier name is required'
                ]);
                break;
            }

            $query = "INSERT INTO suppliers (name, contact_person, phone, email, address) 
                      VALUES (?, ?, ?, ?, ?)";

            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $data->name);
            $contactPerson = $data->contact_person ?? null;
            $stmt->bindParam(2, $contactPerson);
            $phone = $data->phone ?? null;
            $stmt->bindParam(3, $phone);
            $email = $data->email ?? null;
            $stmt->bindParam(4, $email);
            $address = $data->address ?? null;
            $stmt->bindParam(5, $address);

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Supplier added successfully',
                    'id' => $db->lastInsertId()
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to add supplier'
                ]);
            }
            break;

        case 'PUT':
            // Update supplier
            $input = file_get_contents("php://input");
            $data = json_decode($input);

            if (empty($data->id)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Supplier ID is required'
                ]);
                break;
            }

            if (empty($data->name)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Supplier name is required'
                ]);
                break;
            }

            $query = "UPDATE suppliers SET name = ?, contact_person = ?, phone = ?, email = ?, address = ? WHERE id = ?";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $data->name);
            $contactPerson = $data->contact_person ?? null;
            $stmt->bindParam(2, $contactPerson);
            $phone = $data->phone ?? null;
            $stmt->bindParam(3, $phone);
            $email = $data->email ?? null;
            $stmt->bindParam(4, $email);
            $address = $data->address ?? null;
            $stmt->bindParam(5, $address);
            $stmt->bindParam(6, $data->id);

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Supplier updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update supplier'
                ]);
            }
            break;

        case 'DELETE':
            // Soft delete supplier (set is_active = 0)
            if (isset($_GET['id'])) {
                $query = "UPDATE suppliers SET is_active = 0 WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->bindParam(1, $_GET['id']);
                
                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Supplier deleted successfully'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to delete supplier'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Supplier ID is required'
                ]);
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
