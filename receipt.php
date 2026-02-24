<?php
session_start();
include_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

$sale_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($sale_id <= 0) {
    header('Location: ' . (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
    exit;
}

// Get sale details with customer and staff information
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

// Get payment splits if this is a split payment
$payment_splits = [];
if ($sale && ($sale['is_split_payment'] == 1 || $sale['payment_method'] === 'Split Payment')) {
    $query = "SELECT payment_method, amount, reference_number, installment_details 
              FROM payment_splits 
              WHERE sale_id = ? 
              ORDER BY id";
    $stmt = $db->prepare($query);
    $stmt->execute([$sale_id]);
    $payment_splits = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!$sale) {
    header('Location: ' . (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'staff_dashboard.php'));
    exit;
}

// Get sale items grouped by product (ignoring slight price variations for display if necessary)
// We fetch IMEIs/Serials from product_items table using subqueries
$query = "SELECT si.product_id, 
                 MAX(si.unit_price) as unit_price, 
                 SUM(si.quantity) as quantity, 
                 SUM(si.total_price) as total_price,
                 p.brand, p.model,
                 (SELECT GROUP_CONCAT(pi.imei SEPARATOR ', ') 
                  FROM product_items pi 
                  WHERE pi.sale_id = si.sale_id AND pi.product_id = si.product_id 
                  AND pi.imei IS NOT NULL AND pi.imei != '') as all_imeis,
                 (SELECT GROUP_CONCAT(pi.serial_number SEPARATOR ', ') 
                  FROM product_items pi 
                  WHERE pi.sale_id = si.sale_id AND pi.product_id = si.product_id 
                  AND pi.serial_number IS NOT NULL AND pi.serial_number != '') as all_serials
          FROM sale_items si 
          LEFT JOIN products p ON si.product_id = p.id 
          WHERE si.sale_id = ?
          GROUP BY si.product_id, p.brand, p.model
          ORDER BY p.brand, p.model";
$stmt = $db->prepare($query);
$stmt->execute([$sale_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo htmlspecialchars($sale['receipt_number']); ?> - IBS Mobile Shop</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #333;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .receipt-header h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }
        
        .receipt-header .subtitle {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .receipt-info {
            margin-bottom: 15px;
            font-size: 12px;
        }
        
        .receipt-info div {
            margin-bottom: 3px;
        }
        
        .receipt-items {
            margin-bottom: 15px;
        }
        
        .receipt-items table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        .receipt-items th {
            text-align: left;
            border-bottom: 1px solid #ddd;
            padding: 5px 0;
            font-weight: bold;
        }
        
        .receipt-items td {
            padding: 5px 0;
            vertical-align: top;
        }
        
        .receipt-items .item-name {
            font-weight: bold;
        }
        
        .receipt-items .item-details {
            font-size: 10px;
            color: #666;
        }
        
        .receipt-totals {
            border-top: 2px dashed #333;
            padding-top: 10px;
            margin-bottom: 15px;
        }
        
        .receipt-totals div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }
        
        .receipt-totals .total {
            font-weight: bold;
            font-size: 14px;
        }
        
        .receipt-payment {
            background: #f9f9f9;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
        }
        
        .receipt-payment .payment-split {
            margin: 5px 0;
            padding: 8px;
            background: #f9f9f9;
            border-radius: 4px;
            border-left: 3px solid #007bff;
        }
        
        .receipt-payment .payment-method {
            font-weight: bold;
            font-size: 14px;
            color: #007bff;
        }
        
        .receipt-payment .payment-amount {
            float: right;
            font-weight: bold;
            color: #28a745;
        }
        
        .receipt-payment .payment-reference {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
        }
        
        .receipt-payment .installment-details {
            font-size: 10px;
            color: #666;
            font-style: italic;
            margin-top: 2px;
        }
        
        .receipt-footer {
            text-align: center;
            border-top: 2px dashed #333;
            padding-top: 15px;
            font-size: 11px;
            color: #666;
        }
        
        .receipt-actions {
            margin-top: 20px;
            text-align: center;
        }
        
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-print {
            background: #28a745;
        }
        
        .btn-print:hover {
            background: #1e7e34;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .receipt-container {
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }
            
            .receipt-actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container" id="receipt">
        <div class="receipt-header">
            <h1>IBS Mobile Shop</h1>
            <div class="subtitle"><?php echo htmlspecialchars($sale['branch_name']); ?></div>
            <div class="subtitle"><?php echo htmlspecialchars($sale['branch_address']); ?></div>
            <div class="subtitle">Tel: <?php echo htmlspecialchars($sale['branch_phone']); ?></div>
            <div class="subtitle"><?php echo htmlspecialchars($sale['branch_email']); ?></div>
        </div>
        
        <div class="receipt-info">
            <div><strong>Receipt No:</strong> <?php echo htmlspecialchars($sale['receipt_number']); ?></div>
            <div><strong>Date:</strong> <?php echo date('Y-m-d H:i:s', strtotime($sale['sale_date'])); ?></div>
            <div><strong>Staff:</strong> <?php echo htmlspecialchars($sale['staff_name']); ?></div>
            <?php if ($sale['customer_name']): ?>
            <div><strong>Customer:</strong> <?php echo htmlspecialchars($sale['customer_name']); ?></div>
            <?php if ($sale['customer_phone']): ?>
            <div><strong>Customer Phone:</strong> <?php echo htmlspecialchars($sale['customer_phone']); ?></div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="receipt-items">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="item-name"><?php echo htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></div>
                            <div class="item-details">
                                <?php 
                                    $identifiers = [];
                                    if (!empty($item['all_imeis'])) {
                                        $imeis = array_unique(array_filter(explode(', ', $item['all_imeis'])));
                                        if (!empty($imeis)) $identifiers[] = "IMEI: " . implode(', ', $imeis);
                                    }
                                    if (!empty($item['all_serials'])) {
                                        $serials = array_unique(array_filter(explode(', ', $item['all_serials'])));
                                        if (!empty($serials)) $identifiers[] = "SN: " . implode(', ', $serials);
                                    }
                                    echo implode('<br>', array_map('htmlspecialchars', $identifiers));
                                ?>
                            </div>
                        </td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td><?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="receipt-totals">
            <div>
                <span>Subtotal:</span>
                <span><?php echo number_format($sale['total_amount'], 2); ?></span>
            </div>
            <div>
                <span>VAT (15%):</span>
                <span><?php echo number_format($sale['total_amount'] * 0.15, 2); ?></span>
            </div>
            <div class="total">
                <span>Total:</span>
                <span><?php echo number_format($sale['total_amount'] * 1.15, 2); ?></span>
            </div>
        </div>
        
        <div class="receipt-payment">
            <div><strong>Payment Method(s):</strong></div>
            <?php if (!empty($payment_splits)): ?>
                <?php foreach ($payment_splits as $split): ?>
                    <div class="payment-split">
                        <span class="payment-method"><?php echo htmlspecialchars(ucfirst($split['payment_method'])); ?></span>
                        <span class="payment-amount"><?php echo number_format($split['amount'], 2); ?> EGP</span>
                        <?php if ($split['reference_number']): ?>
                            <div class="payment-reference">Ref: <?php echo htmlspecialchars($split['reference_number']); ?></div>
                        <?php endif; ?>
                        <?php if ($split['installment_details']): ?>
                            <div class="installment-details"><?php echo htmlspecialchars($split['installment_details']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="payment-method"><?php echo htmlspecialchars(ucfirst($sale['payment_method'] ?? 'Cash')); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="receipt-footer">
            <div>Thank you for your business!</div>
            <div>Please come again</div>
            <div style="margin-top: 10px;">*** Original Receipt ***</div>
        </div>
    </div>
    
    <div class="receipt-actions">
        <button class="btn btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <a href="<?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'staff_dashboard.php'; ?>" class="btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <script>
        // Auto-print functionality
        window.onload = function() {
            // Optional: Auto-print when page loads
            // window.print();
        };
    </script>
</body>
</html>
