<?php
// Suppress error display to prevent HTML output in JSON responses
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

// Register error handler to catch fatal errors
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

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection
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
        // Handle statistics request
        if (isset($_GET['stats'])) {
            try {
                $df = $_GET['date_from'] ?? date('Y-m-d');
                $dt = $_GET['date_to'] ?? date('Y-m-d');
                $mf = $_GET['month_from'] ?? date('Y-m'); // Keeping month fallback for other views

                // 1. Total Sales in Range
                $q1 = "SELECT COUNT(*) as count FROM sales WHERE DATE(sale_date) >= ? AND DATE(sale_date) <= ? AND status != 'returned'";
                $s1 = $db->prepare($q1);
                $s1->execute([$df, $dt]);
                $sales_range = $s1->fetch(PDO::FETCH_ASSOC)['count'];

                // 2. Total Revenue in Range
                $q2 = "SELECT SUM((si.quantity - si.returned_quantity) * si.unit_price) as total 
                       FROM sale_items si 
                       JOIN sales s ON si.sale_id = s.id 
                       WHERE DATE(s.sale_date) >= ? AND DATE(s.sale_date) <= ? AND s.status != 'returned'";
                $s2 = $db->prepare($q2);
                $s2->execute([$df, $dt]);
                $revenue_range = (float)($s2->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

                // 3. Payment Breakdown in Range
                $q7 = "SELECT ps.payment_method, SUM(ps.amount) as total 
                       FROM payment_splits ps
                       JOIN sales s ON ps.sale_id = s.id
                       WHERE DATE(s.sale_date) >= ? AND DATE(s.sale_date) <= ?
                       AND s.status != 'returned'
                       GROUP BY ps.payment_method";
                $s7 = $db->prepare($q7);
                $s7->execute([$df, $dt]);
                $payment_breakdown = $s7->fetchAll(PDO::FETCH_KEY_PAIR);

                // Ensure all methods exist with at least 0
                $methods = ['Cash', 'Visa', 'Instapay', 'Installment'];
                $final_breakdown = [];
                foreach ($methods as $m) {
                    $final_breakdown[$m] = (float)($payment_breakdown[$m] ?? 0);
                }

                ob_clean();
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'total_transactions' => (int)$sales_range,
                        'total_revenue' => (float)$revenue_range,
                        'payment_breakdown' => $final_breakdown
                    ]
                ]);
                break;
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                break;
            }
        }
        // Handle top products request
        if (isset($_GET['top_products'])) {
            try {
                $cond = [];
                $pars = [];
                if (!empty($_GET['date_from'])) {
                    $cond[] = "DATE(s.sale_date) >= ?";
                    $pars[] = $_GET['date_from'];
                }
                if (!empty($_GET['date_to'])) {
                    $cond[] = "DATE(s.sale_date) <= ?";
                    $pars[] = $_GET['date_to'];
                }
                
                $where = !empty($cond) ? "WHERE " . implode(" AND ", $cond) : "";
                
                $query = "SELECT p.id, (SELECT pi.item_code FROM product_items pi WHERE pi.product_id = p.id LIMIT 1) as product_code, 
                                 CONCAT(p.brand, ' ', p.model) as product_name, 
                                 p.image_url,
                                 SUM(si.quantity - si.returned_quantity) as total_units,
                                 SUM((si.quantity - si.returned_quantity) * si.unit_price) as total_revenue
                          FROM sale_items si
                          JOIN sales s ON si.sale_id = s.id
                          JOIN products p ON si.product_id = p.id
                          $where
                          AND s.status != 'returned'
                          GROUP BY p.id
                          ORDER BY total_revenue DESC
                          LIMIT 10";
                
                $stmt = $db->prepare($query);
                $stmt->execute($pars);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                ob_clean();
                echo json_encode(['success' => true, 'data' => $results]);
                break;
            } catch (Exception $e) {
                ob_clean();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                break;
            }
        }

        // Add test endpoint
        if (isset($_GET['test'])) {
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Sales API is working',
                'timestamp' => date('Y-m-d H:i:s'),
                'database_connected' => $db ? true : false
            ]);
            break;
        }

        // Check if requesting specific sale by ID
        if (isset($_GET['id'])) {
            $sale_id = (int) $_GET['id'];
            error_log("API: Getting sale details for ID: $sale_id");

            // Get specific sale with details
            $query = "SELECT s.*, c.name as customer_name, c.phone as customer_phone, u.name as staff_name
                      FROM sales s
                      LEFT JOIN customers c ON s.customer_id = c.id
                      LEFT JOIN users u ON s.staff_id = u.id
                      WHERE s.id = ?";

            $stmt = $db->prepare($query);
            $stmt->bindParam(1, $sale_id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                // Get sale items with individual unit details
                $items_query = "SELECT si.*, p.brand as product_brand, p.model as product_model,
                               (SELECT pi.item_code FROM product_items pi WHERE pi.product_id = p.id LIMIT 1) as product_code,
                               (SELECT GROUP_CONCAT(pi.imei SEPARATOR ', ') FROM product_items pi WHERE pi.sale_id = si.sale_id AND pi.product_id = si.product_id AND pi.status = 'sold') as imeis,
                               (SELECT GROUP_CONCAT(pi.serial_number SEPARATOR ', ') FROM product_items pi WHERE pi.sale_id = si.sale_id AND pi.product_id = si.product_id AND pi.status = 'sold') as serials,
                               (SELECT GROUP_CONCAT(pi.barcode SEPARATOR ', ') FROM product_items pi WHERE pi.sale_id = si.sale_id AND pi.product_id = si.product_id AND pi.status = 'sold') as barcodes
                               FROM sale_items si
                               JOIN products p ON si.product_id = p.id
                               WHERE si.sale_id = ?";
                $items_stmt = $db->prepare($items_query);
                $items_stmt->execute([$sale_id]);
                $items = [];
                while ($item_row = $items_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $items[] = [
                        'id' => (int)$item_row['id'],
                        'product_id' => (int)$item_row['product_id'],
                        'product_name' => $item_row['product_brand'] . ' ' . $item_row['product_model'],
                        'product_code' => $item_row['product_code'],
                        'unit_price' => (float)$item_row['unit_price'],
                        'quantity' => (int)$item_row['quantity'],
                        'returned_quantity' => (int)$item_row['returned_quantity'],
                        'total_price' => (float)$item_row['total_price'],
                        'imei' => $item_row['imeis'],
                        'serial' => $item_row['serials'],
                        'barcode' => $item_row['barcodes']
                    ];
                }

                ob_clean();
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => (int)$row['id'],
                        'receipt_number' => $row['receipt_number'],
                        'customer_name' => $row['customer_name'] ?? 'Walk-in',
                        'customer_phone' => $row['customer_phone'] ?? '',
                        'staff_name' => $row['staff_name'],
                        'total_amount' => (float)$row['total_amount'],
                        'payment_method' => $row['payment_method'],
                        'sale_date' => $row['sale_date'],
                        'items' => $items
                    ]
                ]);
            } else {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Sale not found']);
            }
        } else {
            // Get all sales with advanced filtering
            $conditions = [];
            $params = [];

            // Filter by Barcode
            if (!empty($_GET['barcode'])) {
                $conditions[] = "s.id IN (SELECT sale_id FROM product_items WHERE barcode = ? OR item_code = ?)";
                $params[] = $_GET['barcode'];
                $params[] = $_GET['barcode'];
            }

            // Filter by Customer Phone
            if (!empty($_GET['phone'])) {
                $conditions[] = "c.phone LIKE ?";
                $params[] = "%" . $_GET['phone'] . "%";
            }

            // Filter by Date Range
            if (!empty($_GET['date_from'])) {
                $conditions[] = "DATE(s.sale_date) >= ?";
                $params[] = $_GET['date_from'];
            }
            if (!empty($_GET['date_to'])) {
                $conditions[] = "DATE(s.sale_date) <= ?";
                $params[] = $_GET['date_to'];
            }

            // Filter by Sale ID / Receipt Number
            if (!empty($_GET['search'])) {
                $conditions[] = "(s.id = ? OR s.receipt_number LIKE ?)";
                $params[] = (int)$_GET['search'];
                $params[] = "%" . $_GET['search'] . "%";
            }

            $where = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $query = "SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.is_walk_in, u.name as staff_name,
                      (SELECT SUM(quantity - returned_quantity) FROM sale_items WHERE sale_id = s.id) as item_count,
                      (SELECT COALESCE(SUM(returned_quantity), 0) FROM sale_items WHERE sale_id = s.id) as returned_units,
                      (SELECT COALESCE(SUM(returned_quantity * unit_price), 0) FROM sale_items WHERE sale_id = s.id) as returned_amount
                      FROM sales s
                      LEFT JOIN customers c ON s.customer_id = c.id
                      LEFT JOIN users u ON s.staff_id = u.id
                      $where
                      ORDER BY s.sale_date DESC";

            $stmt = $db->prepare($query);
            $stmt->execute($params);

            $sales = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $sales[] = [
                    'id' => (int) $row['id'],
                    'receipt_number' => $row['receipt_number'],
                    'customer_name' => $row['customer_name'] ?? 'Walk-in',
                    'customer_phone' => $row['customer_phone'] ?? '',
                    'staff_name' => $row['staff_name'],
                    'total_amount' => (float) $row['total_amount'],
                    'payment_method' => $row['payment_method'],
                    'sale_date' => $row['sale_date'],
                    'item_count' => (int) $row['item_count'],
                    'status' => $row['status'] ?? 'completed',
                    'is_walk_in' => (int)($row['is_walk_in'] ?? 0),
                    'returned_units' => (int)($row['returned_units'] ?? 0),
                    'returned_amount' => (float)($row['returned_amount'] ?? 0)
                ];
            }

            ob_clean();
            echo json_encode([
                'success' => true,
                'data' => $sales
            ]);
        }
        break;
        break;

    case 'POST':
        // Create new sale
        try {
            $input = file_get_contents("php://input");
            if (empty($input)) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'No data received'
                ]);
                break;
            }

            $data = json_decode($input);

            if (json_last_error() !== JSON_ERROR_NONE) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid JSON data: ' . json_last_error_msg()
                ]);
                break;
            }

            if (!$data) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to decode JSON data'
                ]);
                break;
            }

            // Handle Return Action (Full or Partial)
            if (isset($data->action) && $data->action === 'return' && !empty($data->sale_id)) {
                try {
                    $db->beginTransaction();
                    
                    $sale_id = (int)$data->sale_id;
                    $password = $data->password ?? '';
                    $staff_id = $data->staff_id ?? null;
                    $return_items = $data->items ?? null; // Optional array of {item_id, return_qty}

                    // 1. Password Verification
                    if (empty($password)) {
                        throw new Exception("Security verification required for returns");
                    }
                    
                    // Master Password Override
                    if ($password === '@@@') {
                        $verified_user = ['id' => 1, 'role' => 'admin']; // Use system admin role
                    } else {
                        // Cross-check password against users table
                        $v_query = "SELECT id, role FROM users WHERE password = ? AND is_active = 1";
                        $v_stmt = $db->prepare($v_query);
                        $v_stmt->execute([$password]);
                        $verified_user = $v_stmt->fetch(PDO::FETCH_ASSOC);
                    }

                    if (!$verified_user) {
                        throw new Exception("Invalid security password. Access denied.");
                    }

                    // Optional: Restrict return authorization to owner/admin
                    if (!in_array($verified_user['role'], ['owner', 'admin'])) {
                        throw new Exception("Unauthorized: Returns require owner or admin authorization");
                    }
                    
                    // Get sale info
                    $check_query = "SELECT * FROM sales WHERE id = ?";
                    $check_stmt = $db->prepare($check_query);
                    $check_stmt->execute([$sale_id]);
                    $sale = $check_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$sale) {
                        throw new Exception("Sale not found");
                    }
                    
                    if ($sale['status'] === 'returned') {
                        throw new Exception("Sale has already been fully returned");
                    }

                    $total_returned_value = 0;
                    $is_partial = false;
                    $all_items_fully_returned = true;

                    if ($return_items && is_array($return_items)) {
                        // Partial Return Logic
                        $is_partial = true;
                        foreach ($return_items as $r_item) {
                            $si_id = (int)$r_item->item_id;
                            $r_qty = (int)$r_item->return_qty;

                            if ($r_qty <= 0) continue;

                            // Get sale item info
                            $si_query = "SELECT * FROM sale_items WHERE id = ? AND sale_id = ?";
                            $si_stmt = $db->prepare($si_query);
                            $si_stmt->execute([$si_id, $sale_id]);
                            $si = $si_stmt->fetch(PDO::FETCH_ASSOC);

                            if (!$si) continue;

                            $available_to_return = $si['quantity'] - $si['returned_quantity'];
                            if ($r_qty > $available_to_return) {
                                throw new Exception("Return quantity exceeds available quantity for item ID $si_id");
                            }

                            // Update sale_items returned_quantity
                            $update_si = "UPDATE sale_items SET returned_quantity = returned_quantity + ? WHERE id = ?";
                            $db->prepare($update_si)->execute([$r_qty, $si_id]);

                            // Restore product items
                            $restore_query = "UPDATE product_items SET status = 'available', sale_id = NULL 
                                             WHERE sale_id = ? AND product_id = ? AND status = 'sold' 
                                             LIMIT $r_qty";
                            $db->prepare($restore_query)->execute([$sale_id, $si['product_id']]);

                            // Log stock movement
                            $movement_query = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, created_by) 
                                              VALUES (?, 'in', ?, 'return', ?, ?)";
                            $db->prepare($movement_query)->execute([$si['product_id'], $r_qty, $sale_id, $data->staff_id ?? 1]);

                            $total_returned_value += ($r_qty * $si['unit_price']);
                        }

                        // Check if everything is now returned
                        $check_full_query = "SELECT SUM(quantity) as total_q, SUM(returned_quantity) as total_r FROM sale_items WHERE sale_id = ?";
                        $check_full_stmt = $db->prepare($check_full_query);
                        $check_full_stmt->execute([$sale_id]);
                        $totals = $check_full_stmt->fetch(PDO::FETCH_ASSOC);
                        if ($totals['total_r'] < $totals['total_q']) {
                            $all_items_fully_returned = false;
                        }
                    } else {
                        // Full Return Logic
                        // Update sale_items: set returned_quantity = quantity
                        $update_si_all = "UPDATE sale_items SET returned_quantity = quantity WHERE sale_id = ?";
                        $db->prepare($update_si_all)->execute([$sale_id]);

                        // Restore product items
                        $update_items = "UPDATE product_items SET status = 'available', sale_id = NULL WHERE sale_id = ? AND status = 'sold'";
                        $db->prepare($update_items)->execute([$sale_id]);
                        
                        // Log stock movement for return
                        $items_query = "SELECT product_id, (quantity - returned_quantity) as diff FROM sale_items WHERE sale_id = ?";
                        // Note: if someone did a partial return before, we only log the difference
                        $items_stmt = $db->prepare($items_query);
                        $items_stmt->execute([$sale_id]);
                        $sale_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($sale_items as $item) {
                            if ($item['diff'] <= 0) continue;
                            try {
                                $movement_query = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, created_by) 
                                                  VALUES (?, 'in', ?, 'return', ?, ?)";
                                $movement_stmt = $db->prepare($movement_query);
                                $movement_stmt->execute([$item['product_id'], $item['diff'], $sale_id, $data->staff_id ?? 1]);
                            } catch (Exception $e) {
                                error_log("Stock movements error: " . $e->getMessage());
                            }
                        }
                        $total_returned_value = $sale['total_amount']; // We'll just use the sale total for simplicity if it's full return
                    }
                    
                    // Update sale status
                    $new_status = $all_items_fully_returned ? 'returned' : 'partially_returned';
                    $update_sale = "UPDATE sales SET status = ? WHERE id = ?";
                    $db->prepare($update_sale)->execute([$new_status, $sale_id]);
                    
                    // Update customer total purchases
                    if (!empty($sale['customer_id'])) {
                        $update_cust = "UPDATE customers SET total_purchases = total_purchases - ? WHERE id = ?";
                        $db->prepare($update_cust)->execute([$total_returned_value, $sale['customer_id']]);
                    }
                    
                    $db->commit();
                    ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Return processed successfully']);
                    break;
                } catch (Exception $e) {
                    $db->rollback();
                    throw $e;
                }
            }

            // Debug logging
            error_log("Sales API received data: " . json_encode($data));
            error_log("Staff ID: " . ($data->staff_id ?? 'NULL'));
            error_log("Items count: " . (is_array($data->items) ? count($data->items) : 'NOT ARRAY'));

            // Validate required fields
            if (empty($data->items) || !is_array($data->items) || count($data->items) === 0) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'No items in sale'
                ]);
                break;
            }

            if (empty($data->staff_id) || $data->staff_id === null) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Staff ID is required',
                    'received_staff_id' => $data->staff_id ?? 'null'
                ]);
                break;
            }

            // Validate staff_id is numeric
            if (!is_numeric($data->staff_id)) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid staff ID format',
                    'received_staff_id' => $data->staff_id
                ]);
                break;
            }

            if (!empty($data->items) && !empty($data->staff_id) && $data->staff_id !== null) {
                try {
                    $db->beginTransaction();

                    // Generate receipt number
                    $receipt_number = 'RCP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

                    // 1. Handle Auto-Customer Creation/Linking
                    $customer_id = $data->customer_id ?? null;
                    if (!empty($data->customer_phone)) {
                        $phone = $data->customer_phone;
                        $name = $data->customer_name ?? 'Walk-in Customer';
                        
                        // Check if exists
                        $c_check = "SELECT id FROM customers WHERE phone = ?";
                        $c_stmt = $db->prepare($c_check);
                        $c_stmt->execute([$phone]);
                        $existing_c = $c_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($existing_c) {
                            $customer_id = (int)$existing_c['id'];
                            // Update existing customer name and password
                            $c_update = "UPDATE customers SET name = ?, password = ?, is_walk_in = 1 WHERE id = ?";
                            $db->prepare($c_update)->execute([$name, $receipt_number, $customer_id]);
                        } else {
                            // Create new customer
                            $c_insert = "INSERT INTO customers (name, phone, password, is_walk_in, total_purchases) VALUES (?, ?, ?, 1, 0)";
                            $db->prepare($c_insert)->execute([$name, $phone, $receipt_number]);
                            $customer_id = (int)$db->lastInsertId();
                        }
                    }

                    // Insert sale record with staff tracking
                    $sale_query = "INSERT INTO sales (receipt_number, customer_id, staff_id, total_amount, payment_method, is_split_payment) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                    $sale_stmt = $db->prepare($sale_query);
                    $payment_method = $data->is_split_payment ? 'Split' : ($data->payment_splits[0]->payment_method ?? 'Cash');
                    
                    $staff_id = (int) $data->staff_id;

                    $sale_stmt->bindParam(1, $receipt_number);
                    $sale_stmt->bindParam(2, $customer_id);
                    $sale_stmt->bindParam(3, $staff_id);
                    $sale_stmt->bindParam(4, $data->total_amount);
                    $sale_stmt->bindParam(5, $payment_method);
                    $sale_stmt->bindParam(6, $data->is_split_payment);

                    // Log the sale creation
                    error_log("Creating sale: Receipt $receipt_number by staff ID " . $data->staff_id . " (" . ($data->staff_name ?? 'Unknown') . ")");

                    $sale_stmt->execute();
                    $sale_id = $db->lastInsertId();

                    // Group items by product_id and unit_price before inserting
                    $groupedItems = [];
                    foreach ($data->items as $item) {
                        $key = $item->product_id . '_' . $item->unit_price;
                        if (!isset($groupedItems[$key])) {
                            $groupedItems[$key] = [
                                'product_id' => $item->product_id,
                                'unit_price' => $item->unit_price,
                                'quantity' => 0,
                                'total_price' => 0,
                                'product_item_ids' => [],
                                'item_codes' => []
                            ];
                        }
                        $groupedItems[$key]['quantity'] += $item->quantity;
                        $groupedItems[$key]['total_price'] += $item->total_price;
                        
                        // Collect specific item identifiers
                        if (!empty($item->product_item_ids)) {
                            $groupedItems[$key]['product_item_ids'] = array_merge(
                                $groupedItems[$key]['product_item_ids'], 
                                $item->product_item_ids
                            );
                        }
                        if (!empty($item->item_codes)) {
                            $groupedItems[$key]['item_codes'] = array_merge(
                                $groupedItems[$key]['item_codes'], 
                                $item->item_codes
                            );
                        }
                    }

                    // Insert grouped sale items and update stock
                    foreach ($groupedItems as $item) {
                        // Insert sale item
                        $item_query = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) 
                                  VALUES (?, ?, ?, ?, ?)";
                        $item_stmt = $db->prepare($item_query);
                        $unit_price = floatval($item['unit_price']);
                        $total_price = floatval($item['total_price']);
                        $product_id = (int) $item['product_id'];
                        $quantity = (int) $item['quantity'];

                        $item_stmt->bindParam(1, $sale_id);
                        $item_stmt->bindParam(2, $product_id);
                        $item_stmt->bindParam(3, $quantity);
                        $item_stmt->bindParam(4, $unit_price);
                        $item_stmt->bindParam(5, $total_price);
                        $item_stmt->execute();

                        // Update product items status to 'sold'
                        // Use specific item identifiers if provided, otherwise pick first available
                        $units_to_sell = $quantity;
                        if (!empty($item['item_codes']) && is_array($item['item_codes'])) {
                            // Use specific item codes provided
                            foreach ($item['item_codes'] as $item_code) {
                                $item_update_query = "UPDATE product_items SET status = 'sold', sale_id = ? WHERE item_code = ? AND status = 'available'";
                                $item_update_stmt = $db->prepare($item_update_query);
                                $item_update_stmt->execute([$sale_id, $item_code]);
                            }
                        } elseif (!empty($item['product_item_ids']) && is_array($item['product_item_ids'])) {
                            // Use specific product item IDs
                            foreach ($item['product_item_ids'] as $product_item_id) {
                                $item_update_query = "UPDATE product_items SET status = 'sold', sale_id = ? WHERE id = ? AND status = 'available'";
                                $item_update_stmt = $db->prepare($item_update_query);
                                $item_update_stmt->execute([$sale_id, $product_item_id]);
                            }
                        } else {
                            // Automatically pick available items (fallback)
                            $pick_query = "UPDATE product_items SET status = 'sold', sale_id = ? 
                                          WHERE product_id = ? AND status = 'available' 
                                          LIMIT " . (int)$units_to_sell;
                            $pick_stmt = $db->prepare($pick_query);
                            $pick_stmt->execute([$sale_id, $product_id]);
                        }

                        // Insert stock movement record (if table exists)
                        try {
                            $movement_query = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, created_by) 
                                          VALUES (?, 'out', ?, 'sale', ?, ?)";
                            $movement_stmt = $db->prepare($movement_query);
                            $movement_stmt->bindParam(1, $item['product_id']);
                            $movement_stmt->bindParam(2, $item['quantity']);
                            $movement_stmt->bindParam(3, $sale_id);
                            $movement_stmt->bindParam(4, $data->staff_id);
                            $movement_stmt->execute();
                        } catch (Exception $e) {
                            // Stock movements table doesn't exist, continue without it
                            error_log("Stock movements table not found, skipping: " . $e->getMessage());
                        }
                    }

                    // Update customer total purchases if customer exists
                    if (!empty($data->customer_id)) {
                        $customer_query = "UPDATE customers SET total_purchases = total_purchases + ? WHERE id = ?";
                        $customer_stmt = $db->prepare($customer_query);
                        $customer_total = floatval($data->total_amount);
                        $customer_id_val = (int) $data->customer_id;
                        $customer_stmt->bindParam(1, $customer_total);
                        $customer_stmt->bindParam(2, $customer_id_val);
                        $customer_stmt->execute();
                    }

                    // Insert payment splits if applicable
                    if (isset($data->payment_splits) && is_array($data->payment_splits)) {
                        foreach ($data->payment_splits as $split) {
                            $split_query = "INSERT INTO payment_splits (sale_id, payment_method, amount, reference_number) 
                                          VALUES (?, ?, ?, ?)";
                            $split_stmt = $db->prepare($split_query);
                            
                            $split_amount = floatval($split->amount);
                            $split_method = $split->payment_method;
                            $split_reference = $split->reference_number ?? null;
                            
                            $split_stmt->execute([$sale_id, $split_method, $split_amount, $split_reference]);
                        }
                    }

                    $db->commit();

                    // Clean any output buffer before sending JSON
                    ob_clean();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Sale completed successfully',
                        'sale_id' => (int) $sale_id,
                        'receipt_number' => $receipt_number
                    ]);

                } catch (Exception $e) {
                    $db->rollback();
                    error_log("Sale creation failed: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    // Clean any output buffer before sending JSON
                    ob_clean();
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to complete sale: ' . $e->getMessage(),
                        'error_details' => $e->getFile() . ':' . $e->getLine()
                    ]);
                }
            } else {
                $missing = [];
                if (empty($data->items))
                    $missing[] = 'items';
                if (empty($data->staff_id))
                    $missing[] = 'staff_id';

                // Clean any output buffer before sending JSON
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing required data: ' . implode(', ', $missing),
                    'received_data' => [
                        'has_items' => !empty($data->items),
                        'items_count' => is_array($data->items) ? count($data->items) : 0,
                        'staff_id' => $data->staff_id ?? 'null',
                        'staff_id_type' => gettype($data->staff_id ?? null)
                    ]
                ]);
            }
        } catch (Throwable $e) {
            ob_clean();
            http_response_code(500);
            $errorMsg = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            $errorTrace = $e->getTraceAsString();

            error_log("Sales API POST error: " . $errorMsg);
            error_log("Error in file: " . $errorFile . " on line " . $errorLine);
            error_log("Stack trace: " . $errorTrace);

            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $errorMsg,
                'error_details' => $errorFile . ':' . $errorLine,
                'error_type' => get_class($e)
            ]);
        } catch (Exception $e) {
            ob_clean();
            http_response_code(500);
            error_log("Sales API POST exception: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'error_details' => $e->getFile() . ':' . $e->getLine()
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