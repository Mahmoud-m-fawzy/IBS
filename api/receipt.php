<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - No session found']);
    exit;
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get receipt details
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Sale ID is required']);
            exit;
        }

        $sale_id = intval($_GET['id']);

        try {
            // Get sale details
            $query = "SELECT s.*, c.name as customer_name, c.phone as customer_phone, 
                     u.name as staff_name, b.name as branch_name, b.address as branch_address,
                     b.phone as branch_phone, b.email as branch_email
                     FROM sales s 
                     LEFT JOIN customers c ON s.customer_id = c.id 
                     LEFT JOIN users u ON s.staff_id = u.id
                     LEFT JOIN branches b ON b.id = 1
                     WHERE s.id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$sale_id]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$sale) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Sale not found']);
                exit;
            }

            // Get sale items
            $query = "SELECT si.*, p.brand, p.model,
                     (SELECT pi.item_code FROM product_items pi WHERE pi.product_id = p.id LIMIT 1) as code,
                     (SELECT pi.barcode FROM product_items pi WHERE pi.product_id = p.id LIMIT 1) as barcode,
                     (SELECT pi.imei FROM product_items pi WHERE pi.product_id = p.id LIMIT 1) as imei,
                     (SELECT pi.serial_number FROM product_items pi WHERE pi.product_id = p.id LIMIT 1) as serial_number
                     FROM sale_items si 
                     LEFT JOIN products p ON si.product_id = p.id 
                     WHERE si.sale_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$sale_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format data
            $receipt_data = [
                'sale' => [
                    'id' => (int) $sale['id'],
                    'receipt_number' => $sale['receipt_number'],
                    'sale_date' => $sale['sale_date'],
                    'total_amount' => (float) $sale['total_amount'],
                    'payment_method' => $sale['payment_method'] ?? 'Cash',
                    'staff_name' => $sale['staff_name'],
                    'customer_name' => $sale['customer_name'],
                    'customer_phone' => $sale['customer_phone'],
                    'branch_name' => $sale['branch_name'],
                    'branch_address' => $sale['branch_address'],
                    'branch_phone' => $sale['branch_phone'],
                    'branch_email' => $sale['branch_email']
                ],
                'items' => []
            ];

            foreach ($items as $item) {
                $receipt_data['items'][] = [
                    'id' => (int) $item['id'],
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (float) $item['unit_price'],
                    'total_price' => (float) $item['total_price'],
                    'brand' => $item['brand'],
                    'model' => $item['model'],
                    'code' => $item['code'],
                    'barcode' => $item['barcode'],
                    'imei' => $item['imei'],
                    'serial_number' => $item['serial_number']
                ];
            }

            echo json_encode(['success' => true, 'data' => $receipt_data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching receipt: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        // Create new sale and generate receipt
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['items']) || !isset($data['customer_id']) || !isset($data['payment_method'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Items, customer ID, and payment method are required']);
            exit;
        }

        $customer_id = intval($data['customer_id']);
        $payment_method = trim($data['payment_method']);
        $items = $data['items'];

        // Validate payment method
        $valid_payment_methods = ['Cash', 'Visa', 'Instapay', 'Installment'];
        if (!in_array($payment_method, $valid_payment_methods)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid payment method. Must be one of: ' . implode(', ', $valid_payment_methods)]);
            exit;
        }

        if (empty($items)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'At least one item is required']);
            exit;
        }

        try {
            $db->beginTransaction();

            // Generate receipt number
            $receipt_number = 'IBS-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

            // Calculate total amount
            $total_amount = 0;
            foreach ($items as $item) {
                $total_amount += floatval($item['unit_price']) * intval($item['quantity']);
            }

            // Insert sale record
            $query = "INSERT INTO sales (receipt_number, customer_id, staff_id, total_amount, payment_method) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$receipt_number, $customer_id, $_SESSION['user_id'], $total_amount, $payment_method]);
            $sale_id = $db->lastInsertId();

            // Insert sale items
            foreach ($items as $item) {
                $product_id = intval($item['product_id']);
                $quantity = intval($item['quantity']);
                $unit_price = floatval($item['unit_price']);
                $total_price = $unit_price * $quantity;

                $query = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$sale_id, $product_id, $quantity, $unit_price, $total_price]);

                // Update product stock
                $query = "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?";
                $stmt = $db->prepare($query);
                $result = $stmt->execute([$quantity, $product_id, $quantity]);

                if (!$result || $stmt->rowCount() === 0) {
                    throw new Exception("Insufficient stock for product ID: $product_id");
                }

                // Update stock items status to sold
                $query = "UPDATE stock_items SET status = 'sold', sale_id = ? WHERE product_id = ? AND status = 'available' LIMIT ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$sale_id, $product_id, $quantity]);
            }

            // Update customer total purchases
            $query = "UPDATE customers SET total_purchases = total_purchases + ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$total_amount, $customer_id]);

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Sale completed successfully',
                'sale_id' => $sale_id,
                'receipt_number' => $receipt_number,
                'total_amount' => $total_amount,
                'payment_method' => $payment_method
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error creating sale: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
