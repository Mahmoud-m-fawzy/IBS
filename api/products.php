<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Suppress error display to prevent HTML output in JSON responses
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

function sendJsonResponse($data, $status = 200) {
    if (ob_get_level() > 0) ob_clean();
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit();
}

// Register error handler to catch fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Fatal error: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ], 500);
    }
});

try {
    include_once '../config/database.php';

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        sendJsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Database initialization error: ' . $e->getMessage()], 500);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all products with supplier, category, and stock information
        $query = "SELECT p.*, s.name as supplier_name, c.name as category_name,
                  (SELECT item_code FROM product_items WHERE product_id = p.id LIMIT 1) as search_code,
                  (SELECT COUNT(*) FROM product_items WHERE product_id = p.id AND status = 'available') as available_stock,
                  (SELECT COUNT(*) FROM product_items WHERE product_id = p.id) as total_stock,
                  (SELECT COUNT(*) FROM product_items WHERE product_id = p.id 
                   AND imei IS NOT NULL AND imei != '' AND imei NOT LIKE 'P_%' AND imei != 'PENDING_IMEI'
                   AND serial_number IS NOT NULL AND serial_number != '' AND serial_number NOT LIKE 'PENDING_SERIAL%') as serialized_count,
                  (SELECT 
                    CASE 
                        WHEN (imei IS NULL OR imei = '') AND (serial_number IS NULL OR serial_number = '') THEN 'not_serialized'
                        WHEN imei LIKE 'P_%' OR imei = 'PENDING_IMEI' OR serial_number LIKE 'PENDING_SERIAL%' THEN 'pending'
                        ELSE 'serialized'
                    END
                   FROM product_items WHERE product_id = p.id LIMIT 1) as serialization_status
                  FROM products p 
                  LEFT JOIN suppliers s ON p.supplier_id = s.id 
                  LEFT JOIN categories c ON p.category_id = c.id
                  ORDER BY p.brand, p.model";

        $stmt = $db->prepare($query);
        $stmt->execute();

        $products = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $products[] = [
                'id' => (int) $row['id'],
                'code' => $row['search_code'] ?? 'N/A',
                'brand' => $row['brand'],
                'model' => $row['model'],
                'category_name' => $row['category_name'] ?? 'Uncategorized',
                'supplier_name' => $row['supplier_name'] ?? 'Unknown',
                'description' => $row['description'] ?? '',
                'color' => $row['color'] ?? 'N/A',
                'purchase_price' => (float) ($row['purchase_price'] ?? 0),
                'min_selling_price' => (float) ($row['min_selling_price'] ?? 0),
                'suggested_price' => (float) ($row['suggested_price'] ?? 0),
                'available_stock' => (int) $row['available_stock'],
                'total_stock' => (int) $row['total_stock'],
                'serialized_count' => (int) $row['serialized_count'],
                'quantity' => (int) ($row['quantity'] ?? 0),
                'min_stock' => $row['min_stock'] !== null ? (int)$row['min_stock'] : null,
                'serialization_status' => $row['serialization_status'] ?? 'not_serialized',
                'image_url' => $row['image_url'] ?? '',
                'is_active' => (bool) $row['is_active'],
                'created_at' => $row['created_at'] ?? null
            ];
        }

        sendJsonResponse([
            'success' => true,
            'data' => $products
        ]);
        break;

    case 'POST':
        // Add new product (Admin only)
        $data = json_decode(file_get_contents("php://input"));

        // Validate required fields and values
        $purchase_price = floatval($data->purchase_price ?? 0);
        $min_selling_price = floatval($data->min_selling_price ?? 0);
        $suggested_price = floatval($data->suggested_price ?? 0);
        $stock = intval($data->stock ?? 0);

        if ((empty($data->brand) && empty($data->brand_id)) || empty($data->model)) {
            sendJsonResponse(['success' => false, 'message' => 'Brand and Model are required fields']);
        } elseif ($purchase_price <= 0) {
            sendJsonResponse(['success' => false, 'message' => 'Purchase price must be greater than 0']);
        } elseif ($min_selling_price <= 0) {
            sendJsonResponse(['success' => false, 'message' => 'Minimum selling price must be greater than 0']);
        } elseif ($suggested_price <= 0) {
            sendJsonResponse(['success' => false, 'message' => 'Suggested selling price must be greater than 0']);
        } elseif ($min_selling_price < $purchase_price) {
            sendJsonResponse(['success' => false, 'message' => 'Minimum selling price cannot be less than purchase price']);
        } elseif ($suggested_price < $min_selling_price) {
            sendJsonResponse(['success' => false, 'message' => 'Suggested selling price cannot be less than minimum selling price']);
        } elseif ($stock <= 0) {
            sendJsonResponse(['success' => false, 'message' => 'Stock quantity must be greater than 0']);
        } else {
            // Handle brand data - get brand name if brand_id is provided
            $brandName = null;
            $brandId = null;
            
            if (!empty($data->brand_id) && is_numeric($data->brand_id)) {
                // Get brand name from brands table
                $brandQuery = "SELECT name FROM brands WHERE id = ? AND is_active = 1";
                $brandStmt = $db->prepare($brandQuery);
                $brandStmt->bindParam(1, $data->brand_id);
                $brandStmt->execute();
                $brandResult = $brandStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($brandResult) {
                    $brandName = $brandResult['name'];
                    $brandId = (int)$data->brand_id;
                }
            } elseif (!empty($data->brand)) {
                // Handle legacy brand text input
                $brandName = $data->brand;
                
                // Try to find existing brand by name
                $findBrandQuery = "SELECT id FROM brands WHERE name = ? AND is_active = 1";
                $findBrandStmt = $db->prepare($findBrandQuery);
                $findBrandStmt->bindParam(1, $brandName);
                $findBrandStmt->execute();
                $findResult = $findBrandStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($findResult) {
                    $brandId = (int)$findResult['id'];
                } else {
                    // Create new brand if it doesn't exist
                    $insertBrandQuery = "INSERT INTO brands (name) VALUES (?)";
                    $insertBrandStmt = $db->prepare($insertBrandQuery);
                    $insertBrandStmt->bindParam(1, $brandName);
                    if ($insertBrandStmt->execute()) {
                        $brandId = (int)$db->lastInsertId();
                    }
                }
            }
            
            if (empty($brandName)) {
                sendJsonResponse(['success' => false, 'message' => 'Brand is required']);
            }

            // 1. Generate core prefix for tracking
            $catShort = !empty($data->category_name) ? strtoupper(substr($data->category_name, 0, 3)) : 'GEN';
            $brdShort = strtoupper(substr($brandName, 0, 3));
            $modShort = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $data->model), 0, 5));
            $prefix = "$catShort-$brdShort-$modShort";
            
            // 2. Find next sequential number from existing item_codes
            $codeQuery = "SELECT MAX(CAST(SUBSTRING_INDEX(item_code, '-', -1) AS UNSIGNED)) as max_num 
                         FROM product_items WHERE item_code LIKE ?";
            $codeStmt = $db->prepare($codeQuery);
            $searchPrefix = "$prefix-%";
            $codeStmt->execute([$searchPrefix]);
            $result = $codeStmt->fetch(PDO::FETCH_ASSOC);

            $nextNumber = ($result['max_num'] ?? 0) + 1;
            $mainCodePreview = $prefix . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // 3. Handle Image Upload (Base64)
            $imageUrl = '';
            if (!empty($data->image_base64)) {
                $imgData = $data->image_base64;
                if (preg_match('/^data:image\/(\w+);base64,/', $imgData, $type)) {
                    $imgData = substr($imgData, strpos($imgData, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, etc
                    $imgData = base64_decode($imgData);

                    $imgDir = '../images/products/';
                    if (!is_dir($imgDir)) mkdir($imgDir, 0777, true);
                    
                    $imgName = time() . '_' . uniqid() . '.' . $type;
                    $filePath = $imgDir . $imgName;
                    if (file_put_contents($filePath, $imgData)) {
                        $imageUrl = 'images/products/' . $imgName;
                    }
                }
            }

            // 4. Resolve Branch ID (Session or Default)
            $branchId = $_SESSION['branch_id'] ?? 1;

            // 5. Insert main product info
            $minStock = isset($data->min_stock) ? intval($data->min_stock) : 0;
            $categoryId = isset($data->category_id) ? intval($data->category_id) : 0;
            $categoryName = $data->category_name ?? '';
            $desc = $data->description ?? '';
            $color = $data->color ?? null;
            $suppId = $data->supplier_id ?? null;
            $stock = isset($data->stock) ? intval($data->stock) : 0;
            $hasImei = isset($data->has_imei) ? intval($data->has_imei) : 0;
            $hasSerial = isset($data->has_serial) ? intval($data->has_serial) : 0;

            $query = "INSERT INTO products (brand, model, purchase_price, min_selling_price, suggested_price, min_stock, category_id, category, description, color, supplier_id, brand_id, quantity, branch_id, image_url) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($query);
            $stmt->execute([
                $brandName, $data->model, $purchase_price, $min_selling_price, $suggested_price,
                $minStock, $categoryId, $categoryName, $desc, $color, $suppId, $brandId, $stock, $branchId, $imageUrl
            ]);

            if ($db->lastInsertId()) {
                $productId = $db->lastInsertId();
                
                // 6. Create individual unit records in product_items
                $itemsCreated = 0;
                for ($i = 0; $i < $stock; $i++) {
                    $uniqueNum = $nextNumber + $i;
                    $itemCode = $prefix . '-' . str_pad($uniqueNum, 4, '0', STR_PAD_LEFT);
                    
                    // Generate unique EAN-13 barcode
                    $itemBarcode = generateEAN13Barcode($itemCode);
                    
                    // Set IMEI and Serial based on checkbox selections
                    $imei = null;
                    $serialNumber = null;
                    
                    if ($hasImei) {
                        // Set unique placeholder within 20 character limit for IMEI
                        $imei = 'P_' . $productId . '_' . $uniqueNum;
                    }
                    
                    if ($hasSerial) {
                        // Set unique placeholder for serial (50 char limit)
                        $serialNumber = 'PENDING_SERIAL_' . $productId . '_' . $itemCode;
                    }
                    
                    $itemQuery = "INSERT INTO product_items (product_id, item_code, barcode, imei, serial_number, status) VALUES (?, ?, ?, ?, ?, 'available')";
                    $itemStmt = $db->prepare($itemQuery);
                    if ($itemStmt->execute([$productId, $itemCode, $itemBarcode, $imei, $serialNumber])) {
                        $itemsCreated++;
                    }
                }

                sendJsonResponse([
                    'success' => true,
                    'message' => "Successfully added product and created $itemsCreated individual items.",
                    'code' => $mainCodePreview,
                    'id' => $productId
                ]);
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Failed to add product to database']);
            }
        }
        break;

    case 'PUT':
        // Update product details or stock
        $input = file_get_contents("php://input");
        $data = json_decode($input);

        if (!empty($data->id)) {
            // Check if this is a stock-only update (for sales) or full product update
            if (isset($data->brand) && isset($data->model) && (isset($data->price) || isset($data->suggested_price))) {
                // Full product update
                $price = floatval($data->price ?? $data->suggested_price ?? 0);
                $stock = intval($data->stock);
                $minStock = isset($data->min_stock) ? intval($data->min_stock) : null;
                $categoryId = isset($data->category_id) ? intval($data->category_id) : null;
                $imageUrl = isset($data->image_url) ? $data->image_url : null;
                $isActive = isset($data->is_active) ? intval($data->is_active) : null;

                // Validate input
                if (empty($data->brand) || empty($data->model)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Brand and Model are required fields'
                    ]);
                    break;
                }

                if ($price <= 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Price must be greater than 0'
                    ]);
                    break;
                }

                if ($stock < 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Stock quantity cannot be negative'
                    ]);
                    break;
                }

                // Build dynamic update to avoid overwriting when optional fields are omitted
                // Map UI 'price' to DB 'suggested_price' (dump schema has no 'price' column)
                $fields = [
                    'brand' => $data->brand,
                    'model' => $data->model,
                    'suggested_price' => $price,
                    'description' => ($data->description ?? '')
                ];
                if ($minStock !== null) {
                    $fields['min_stock'] = $minStock;
                }
                if ($categoryId !== null) {
                    $fields['category_id'] = $categoryId;
                }
                if ($imageUrl !== null) {
                    $fields['image_url'] = $imageUrl;
                }
                if ($isActive !== null) {
                    $fields['is_active'] = $isActive;
                }
                if (isset($data->purchase_price) && $data->purchase_price !== '') {
                    $fields['purchase_price'] = floatval($data->purchase_price);
                }
                if (isset($data->min_selling_price) && $data->min_selling_price !== '') {
                    $fields['min_selling_price'] = floatval($data->min_selling_price);
                }
                // Add new fields
                if (isset($data->color)) {
                    $fields['color'] = $data->color;
                }
                if (isset($data->supplier_id)) {
                    $fields['supplier_id'] = $data->supplier_id;
                }
                if (isset($data->quantity)) {
                    $fields['quantity'] = (int)$data->quantity;
                }

                $setClauses = [];
                $params = [];
                foreach ($fields as $column => $value) {
                    $setClauses[] = "$column = ?";
                    $params[] = $value;
                }
                $params[] = $data->id;

                $query = "UPDATE products SET " . implode(', ', $setClauses) . " WHERE id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute($params)) {
                    sendJsonResponse(['success' => true, 'message' => 'Product updated successfully']);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Failed to update product']);
                }
            } elseif (isset($data->is_active)) {
                // Status-only update (activate/deactivate)
                $isActive = intval($data->is_active);
                $productId = intval($data->id);
                $query = "UPDATE products SET is_active = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$isActive, $productId])) {
                    sendJsonResponse(['success' => true, 'message' => 'Product ' . ($isActive ? 'activated' : 'deactivated') . ' successfully']);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Failed to update product status']);
                }
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Missing required fields for update']);
            }

        } else {
            sendJsonResponse(['success' => false, 'message' => 'Product ID is required']);
        }
        break;

    case 'DELETE':
        // Delete product (Admin only)
        try {
            $input = file_get_contents("php://input");
            if (empty($input)) {
                sendJsonResponse(['success' => false, 'message' => 'No data received']);
            }

            $data = json_decode($input);

            if (json_last_error() !== JSON_ERROR_NONE) {
                sendJsonResponse(['success' => false, 'message' => 'Invalid JSON data: ' . json_last_error_msg()]);
            }

            if (!$data || empty($data->id)) {
                sendJsonResponse(['success' => false, 'message' => 'Product ID is required']);
            }

            $productId = intval($data->id);

            if ($productId <= 0) {
                sendJsonResponse(['success' => false, 'message' => 'Invalid product ID']);
            }

            // Check if product exists and has no sales
            $checkQuery = "SELECT COUNT(*) as sale_count FROM sale_items WHERE product_id = ?";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(1, $productId);
            $checkStmt->execute();
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($result['sale_count'] > 0) {
                sendJsonResponse(['success' => false, 'message' => 'Cannot delete product that has been sold. Please use the "Deactivate" button instead to hide it from the inventory.']);
            }

            // Delete the product
            $deleteQuery = "DELETE FROM products WHERE id = ?";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(1, $productId);

            if ($deleteStmt->execute()) {
                if ($deleteStmt->rowCount() > 0) {
                    sendJsonResponse(['success' => true, 'message' => 'Product deleted successfully']);
                } else {
                    sendJsonResponse(['success' => false, 'message' => 'Product not found']);
                }
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Failed to delete product']);
            }
        } catch (Throwable $e) {
            error_log("Delete product error: " . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'message' => 'Error deleting product: ' . $e->getMessage(),
                'error_details' => $e->getFile() . ':' . $e->getLine()
            ], 500);
        } catch (Exception $e) {
            error_log("Delete product exception: " . $e->getMessage());
            sendJsonResponse(['success' => false, 'message' => 'Error deleting product: ' . $e->getMessage()], 500);
        }
        break;

    default:
        sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Barcode generation function
function generateEAN13Checksum($digits) {
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $digit = (int) $digits[$i];
        if ($i % 2 == 0) {
            $sum += $digit;
        } else {
            $sum += $digit * 3;
        }
    }
    $checksum = (10 - ($sum % 10)) % 10;
    return $checksum;
}

function generateRandomIMEI() {
    // Generate a random 15-digit IMEI number
    $imei = '';
    for ($i = 0; $i < 14; $i++) {
        $imei .= mt_rand(0, 9);
    }
    
    // Calculate Luhn checksum for the first 14 digits
    $sum = 0;
    $double = false;
    for ($i = 13; $i >= 0; $i--) {
        $digit = (int)$imei[$i];
        if ($double) {
            $digit *= 2;
            if ($digit > 9) {
                $digit = ($digit % 10) + 1;
            }
        }
        $sum += $digit;
        $double = !$double;
    }
    
    $checksum = (10 - ($sum % 10)) % 10;
    return $imei . $checksum;
}

function generateEAN13Barcode($productCode) {
    // Extract numeric part from product code
    $numeric = preg_replace('/[^0-9]/', '', $productCode);
    
    // Pad to 12 digits (EAN-13 without checksum)
    if (strlen($numeric) < 12) {
        $numeric = str_pad($numeric, 12, '0', STR_PAD_LEFT);
    } elseif (strlen($numeric) > 12) {
        $numeric = substr($numeric, 0, 12);
    }
    
    // Calculate checksum
    $checksum = calculateEAN13Checksum($numeric);
    
    return $numeric . $checksum;
}

function calculateEAN13Checksum($digits) {
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $digit = (int) $digits[$i];
        if ($i % 2 == 0) {
            $sum += $digit;
        } else {
            $sum += $digit * 3;
        }
    }
    $checksum = (10 - ($sum % 10)) % 10;
    return $checksum;
}

// End output buffering
ob_end_flush();
?>