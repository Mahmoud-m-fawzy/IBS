<?php
// Suppress error display to prevent HTML output in JSON responses
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
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
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database initialization error: ' . $e->getMessage()
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all brands from the brands table
        $query = "SELECT id, name, description, logo_url, website, contact_email 
                  FROM brands 
                  WHERE is_active = 1 
                  ORDER BY name ASC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        $brands = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $brands[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'description' => $row['description'] ?? '',
                'logo_url' => $row['logo_url'] ?? '',
                'website' => $row['website'] ?? '',
                'contact_email' => $row['contact_email'] ?? ''
            ];
        }

        ob_clean();
        echo json_encode([
            'success' => true,
            'data' => $brands
        ]);
        break;

    case 'POST':
        // Add new brand
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->name)) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Brand name is required'
            ]);
            break;
        }

        // Check if brand already exists
        $checkQuery = "SELECT id FROM brands WHERE name = ?";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(1, $data->name);
        $checkStmt->execute();

        if ($checkStmt->fetch()) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Brand already exists'
            ]);
            break;
        }

        // Insert new brand
        $query = "INSERT INTO brands (name, description, logo_url, website, contact_email) 
                  VALUES (?, ?, ?, ?, ?)";

        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $data->name);
        $description = $data->description ?? '';
        $stmt->bindParam(2, $description);
        $logoUrl = $data->logo_url ?? '';
        $stmt->bindParam(3, $logoUrl);
        $website = $data->website ?? '';
        $stmt->bindParam(4, $website);
        $contactEmail = $data->contact_email ?? '';
        $stmt->bindParam(5, $contactEmail);

        if ($stmt->execute()) {
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Brand added successfully',
                'id' => $db->lastInsertId()
            ]);
        } else {
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Failed to add brand'
            ]);
        }
        break;

    default:
        ob_clean();
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
}

// End output buffering
ob_end_flush();
?>
