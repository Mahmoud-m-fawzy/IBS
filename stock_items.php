<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get product details
$product_id = $_GET['product_id'] ?? 0;
$product = null;

if ($product_id > 0) {
    $query = "SELECT * FROM products WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$product) {
    header('Location: admin_dashboard.php');
    exit();
}

// Get existing stock items
$stock_items_query = "SELECT * FROM stock_items WHERE product_id = ? ORDER BY created_at DESC";
$stock_items_stmt = $db->prepare($stock_items_query);
$stock_items_stmt->execute([$product_id]);
$stock_items = $stock_items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Items Management - <?php echo htmlspecialchars($product['brand'] . ' ' . $product['model']); ?></title>
    <link rel="stylesheet" href="components/css/style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: var(--primary-blue);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        .stock-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .add-items-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .existing-items-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .imei-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .imei-input {
            flex: 1;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        .add-imei-btn {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .save-btn {
            background: var(--success);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        .back-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            display: inline-block;
            position: relative;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            background: #0056b3;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .items-table th,
        .items-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .items-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            color: white;
        }
        .status-available { background: #28a745; }
        .status-sold { background: #007bff; }
        .status-reserved { background: #ffc107; color: #000; }
        .status-damaged { background: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>📋 Stock Items Management</h1>
                <h2><?php echo htmlspecialchars($product['brand'] . ' ' . $product['model']); ?></h2>
                <p>Product Code: <?php echo htmlspecialchars($product['code']); ?></p>
                <p>Current Stock: <?php echo $product['stock']; ?> items</p>
            </div>
            <a href="admin_dashboard.php" onclick="localStorage.setItem('activeTab', 'inventory')" class="back-btn">← Back to Dashboard</a>
        </div>

        <div class="stock-grid">
            <div class="add-items-section">
                <h3>📱 Add Stock Items</h3>
                <?php if ($product['has_imei']): ?>
                    <p style="color: #666; margin-bottom: 20px;">
                        This product requires IMEI numbers. Please enter IMEI for each stock item.
                    </p>
                    <div id="imei-container">
                        <div class="imei-input-group">
                            <input type="text" class="imei-input" placeholder="Enter IMEI number (15 digits)" maxlength="15">
                            <button type="button" class="remove-btn" onclick="removeImeiInput(this)">×</button>
                        </div>
                    </div>
                    <button type="button" class="add-imei-btn" onclick="addImeiInput()">+ Add More IMEI</button>
                <?php else: ?>
                    <p style="color: #666; margin-bottom: 20px;">
                        This product doesn't require IMEI numbers. Stock items will be created with serial numbers only.
                    </p>
                    <div>
                        <label>Number of items to add:</label>
                        <input type="number" id="quantity-input" min="1" value="1" style="width: 100px; padding: 10px; border: 2px solid #ddd; border-radius: 5px;">
                    </div>
                <?php endif; ?>
                
                <button type="button" class="save-btn" onclick="saveStockItems()">
                    💾 Save Stock Items
                </button>
            </div>

            <div class="existing-items-section">
                <h3>📦 Existing Stock Items</h3>
                <?php if (count($stock_items) > 0): ?>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Serial Number</th>
                                <th>IMEI</th>
                                <th>Status</th>
                                <th>Added Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stock_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['serial_number']); ?></td>
                                    <td><?php echo $item['imei'] ? htmlspecialchars($item['imei']) : 'N/A'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $item['status']; ?>">
                                            <?php echo strtoupper($item['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($item['created_at'])); ?></td>
                                    <td>
                                        <?php if ($item['status'] === 'available'): ?>
                                            <button onclick="deleteStockItem(<?php echo $item['id']; ?>)" 
                                                    style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
                                                Delete
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 40px;">
                        No stock items found for this product.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function addImeiInput() {
            const container = document.getElementById('imei-container');
            const inputGroup = document.createElement('div');
            inputGroup.className = 'imei-input-group';
            inputGroup.innerHTML = `
                <input type="text" class="imei-input" placeholder="Enter IMEI number (15 digits)" maxlength="15">
                <button type="button" class="remove-btn" onclick="removeImeiInput(this)">×</button>
            `;
            container.appendChild(inputGroup);
        }

        function removeImeiInput(button) {
            const container = document.getElementById('imei-container');
            if (container.children.length > 1) {
                button.parentElement.remove();
            }
        }

        async function saveStockItems() {
            const productId = <?php echo $product_id; ?>;
            const hasImei = <?php echo $product['has_imei']; ?>;
            
            let items = [];
            
            if (hasImei) {
                // Collect IMEI inputs
                const imeiInputs = document.querySelectorAll('.imei-input');
                for (let input of imeiInputs) {
                    const imei = input.value.trim();
                    if (imei) {
                        items.push({
                            imei: imei,
                            serial_number: 'SN' + Date.now() + Math.random().toString(36).substr(2, 9)
                        });
                    }
                }
            } else {
                // Create items without IMEI
                const quantity = parseInt(document.getElementById('quantity-input').value);
                for (let i = 0; i < quantity; i++) {
                    items.push({
                        serial_number: 'SN' + Date.now() + Math.random().toString(36).substr(2, 9) + i
                    });
                }
            }
            
            if (items.length === 0) {
                alert('Please enter at least one IMEI number or specify quantity');
                return;
            }
            
            try {
                const response = await fetch('api/stock_items.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        items: items
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Stock items added successfully!');
                    location.reload();
                } else {
                    alert('Failed to add stock items: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error adding stock items');
            }
        }

        async function deleteStockItem(itemId) {
            if (!confirm('Delete this stock item? This action cannot be undone.')) return;
            
            try {
                const response = await fetch(`api/stock_items.php?id=${itemId}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Stock item deleted successfully');
                    location.reload();
                } else {
                    alert('Failed to delete stock item: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error deleting stock item');
            }
        }

        // IMEI format validation
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('imei-input')) {
                let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                if (value.length > 15) {
                    value = value.substring(0, 15);
                }
                e.target.value = value;
            }
        });
    </script>
</body>
</html>
