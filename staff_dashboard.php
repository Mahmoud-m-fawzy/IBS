<?php
session_start();

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: index.php');
    exit;
}

include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate="navigation.dashboard">Staff Dashboard - IBS Mobile Shop</title>
    <link rel="stylesheet" href="components/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="components/js/translations.js"></script>
</head>

<body id="body-lang">
    <!-- Language Toggle Button -->
    <button class="language-toggle" id="languageToggle" onclick="toggleLanguage()" title="Toggle Language">
        <i class="fas fa-language"></i>
        <span class="lang-text">EN</span>
    </button>
    
    <div class="header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="components/css/logo.jpeg" alt="IBS Store Logo" style="width: 40px; height: auto; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);" />
            <h1 data-translate="navigation.dashboard">📱 IBS Staff Dashboard</h1>
        </div>
        <div class="user-info">
            <span data-translate="navigation.welcome">Welcome</span>, <?php echo $_SESSION['name']; ?>
            <a href="?logout=1" class="logout-btn" data-translate="navigation.logout">Logout</a>
        </div>
    </div>

    <div class="nav-tabs">
        <button class="nav-tab active" onclick="showTab('receipt')" data-translate="sales.receipt">🧾 Receipt</button>
        <button class="nav-tab" onclick="showTab('inventory')" data-translate="navigation.inventory">📦 Inventory</button>
    </div>

    <div class="content">
        <?php include 'views/staff/staff_receipt_tab.php'; ?>

        <?php include 'views/staff/staff_inventory_tab.php'; ?>
    </div>

    <script>
        // Global variables
        let paymentRowCount = 1;

        function addPaymentRow() {
            const container = document.getElementById('splitPaymentContainer');
            const newRow = document.createElement('div');
            newRow.className = 'payment-method-row';
            newRow.setAttribute('data-payment-row', paymentRowCount);
            
            newRow.innerHTML = `
                <select class="payment-method-select" style="width: 120px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; margin-right: 10px;">
                    <option value="">Select Method</option>
                    <option value="Cash">Cash</option>
                    <option value="Visa">Visa</option>
                    <option value="Instapay">Instapay</option>
                    <option value="Installment">Installment</option>
                </select>
                <input type="number" class="payment-amount" placeholder="Amount" style="width: 100px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; margin-right: 10px;" step="0.01" min="0">
                <input type="text" class="payment-reference" placeholder="Reference (optional)" style="width: 150px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; margin-right: 10px;">
                <button class="btn btn-sm btn-danger" onclick="removePaymentRow(${paymentRowCount})">×</button>
            `;
            
            container.appendChild(newRow);
            
            // Add event listeners to new inputs
            newRow.querySelector('.payment-method-select').addEventListener('change', updatePaymentTotals);
            newRow.querySelector('.payment-amount').addEventListener('input', updatePaymentTotals);
            
            paymentRowCount++;
            updatePaymentTotals();
        }

        function removePaymentRow(rowId) {
            const row = document.querySelector(`[data-payment-row="${rowId}"]`);
            if (row) {
                row.remove();
                updatePaymentTotals();
            }
        }

        function updatePaymentTotals() {
            const totalAmount = parseFloat(currentReceipt.total) || 0;
            let totalPaid = 0;
            
            document.querySelectorAll('.payment-method-row').forEach(row => {
                const amount = parseFloat(row.querySelector('.payment-amount').value) || 0;
                const method = row.querySelector('.payment-method-select').value;
                
                if (method && amount > 0) {
                    totalPaid += amount;
                }
            });
            
            const remaining = totalAmount - totalPaid;
            
            document.getElementById('totalPaid').textContent = totalPaid.toFixed(2);
            document.getElementById('remainingAmount').textContent = remaining.toFixed(2);
            
            // Update remaining amount color
            const remainingElement = document.getElementById('remainingAmount');
            if (Math.abs(remaining) < 0.01) {
                remainingElement.style.color = 'green';
            } else if (remaining > 0) {
                remainingElement.style.color = 'red';
            } else {
                remainingElement.style.color = 'orange';
            }
            
            // Enable complete button only if payment is fully covered
            const completeBtn = document.getElementById('complete-btn');
            completeBtn.disabled = currentReceipt.items.length === 0 || Math.abs(remaining) > 0.01;
        }

        function getPaymentSplits() {
            const splits = [];
            
            document.querySelectorAll('.payment-method-row').forEach(row => {
                const method = row.querySelector('.payment-method-select').value;
                const amount = parseFloat(row.querySelector('.payment-amount').value) || 0;
                const reference = row.querySelector('.payment-reference').value || null;
                
                if (method && amount > 0) {
                    splits.push({
                        payment_method: method,
                        amount: amount,
                        reference_number: reference
                    });
                }
            });
            
            return splits;
        }

        // Add event listeners to initial payment row
        document.addEventListener('DOMContentLoaded', function() {
            const firstRow = document.querySelector('.payment-method-row');
            if (firstRow) {
                firstRow.querySelector('.payment-method-select').addEventListener('change', updatePaymentTotals);
                firstRow.querySelector('.payment-amount').addEventListener('input', updatePaymentTotals);
            }
        });

        let currentReceipt = {
            items: [],
            total: 0
        };
        let products = [];
        let selectedProduct = null;
        let allProducts = [];
        let allInventoryData = [];

        // Initialize
        document.addEventListener('DOMContentLoaded', function () {
            loadProducts();
            loadInventory();
        });

        // Tab functionality
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // Load products
        async function loadProducts() {
            try {
                const response = await fetch('api/products.php');
                const result = await response.json();

                if (result.success) {
                    products = result.data;
                    allProducts = result.data; // Include all products for search functionality
                    populateProductSelect();
                }
            } catch (error) {
                console.error('Error loading products:', error);
            }
        }

        // Populate product select (keeping for compatibility)
        function populateProductSelect() {
            // This function is kept for compatibility but not used with search
        }

        // Product search functions with barcode scanning support
        let scanTimeout = null;
        let isScanning = false;

        function handleProductInput(value) {
            console.log('Input received:', value);
            
            // Detect potential barcode scanning (rapid input of numbers)
            if (/^\d+$/.test(value) && value.length >= 8) {
                console.log('Barcode mode detected');
                if (!isScanning) {
                    isScanning = true;
                    document.getElementById('scan-feedback').style.display = 'block';
                    document.getElementById('scan-indicator').textContent = '🔄';
                }
                
                // Clear existing timeout
                if (scanTimeout) {
                    clearTimeout(scanTimeout);
                }
                
                // Set timeout to process barcode after scanning stops
                scanTimeout = setTimeout(() => {
                    processBarcode(value);
                    isScanning = false;
                    document.getElementById('scan-feedback').style.display = 'none';
                    document.getElementById('scan-indicator').textContent = '📷';
                }, 500);
            } else {
                console.log('Text mode detected');
                // Regular typing search
                if (scanTimeout) {
                    clearTimeout(scanTimeout);
                }
                isScanning = false;
                document.getElementById('scan-feedback').style.display = 'none';
                document.getElementById('scan-indicator').textContent = '📷';
            }
        }

        function processBarcode(barcode) {
            // First try exact barcode match
            let product = allProducts.find(p => p.barcode === barcode || p.imei === barcode);
            
            // If no exact match, try product code match
            if (!product) {
                product = allProducts.find(p => p.code === barcode);
            }
            
            // If still no match, try partial match
            if (!product) {
                product = allProducts.find(p => 
                    p.code.includes(barcode) || 
                    (p.barcode && p.barcode.includes(barcode)) ||
                    (p.imei && p.imei.includes(barcode))
                );
            }
            
            if (product) {
                selectProduct(product);
                // Clear the search input after successful scan
                document.getElementById('product-search').value = '';
                // Show success feedback
                showScanFeedback('✅ Product found: ' + product.brand + ' ' + product.model, 'success');
            } else {
                // Show error feedback
                showScanFeedback('❌ No product found for barcode: ' + barcode, 'error');
                // Clear the input for next scan
                setTimeout(() => {
                    document.getElementById('product-search').value = '';
                }, 2000);
            }
        }

        function showScanFeedback(message, type) {
            const feedback = document.getElementById('scan-feedback');
            feedback.textContent = message;
            feedback.style.display = 'block';
            feedback.style.color = type === 'success' ? '#28a745' : '#dc3545';
            
            setTimeout(() => {
                feedback.style.display = 'none';
            }, 3000);
        }

        // Test search function
        function testSearch() {
            console.log('=== SEARCH TEST ===');
            console.log('All products:', allProducts);
            console.log('Products length:', allProducts.length);
            
            // Wait a moment for products to load
            setTimeout(() => {
                console.log('After timeout - Products length:', allProducts.length);
                if (allProducts.length > 0) {
                    console.log('Testing search with "iPhone"...');
                    searchProducts('iPhone');
                } else {
                    alert('No products loaded. Check console for errors.');
                    console.log('Trying to load products manually...');
                    loadProducts();
                }
            }, 1000);
        }

        function searchProducts(searchTerm) {
            console.log('Search called with:', searchTerm);
            console.log('All products count:', allProducts.length);
            
            const resultsDiv = document.getElementById('product-search-results');

            if (!searchTerm.trim()) {
                resultsDiv.style.display = 'none';
                return;
            }

            const searchLower = searchTerm.toLowerCase();
            const filteredProducts = allProducts.filter(product => {
                return product.code.toLowerCase().includes(searchLower) ||
                    product.brand.toLowerCase().includes(searchLower) ||
                    product.model.toLowerCase().includes(searchLower) ||
                    `${product.brand} ${product.model}`.toLowerCase().includes(searchLower) ||
                    (product.barcode && product.barcode.includes(searchLower)) ||
                    (product.imei && product.imei.includes(searchLower));
            });

            console.log('Filtered products:', filteredProducts.length);

            if (filteredProducts.length === 0) {
                resultsDiv.innerHTML = '<div style="padding: 10px; color: #666;">No products found</div>';
            } else {
                resultsDiv.innerHTML = filteredProducts.map(product => `
                    <div onclick="selectProduct(${JSON.stringify(product).replace(/"/g, '&quot;')})" 
                         style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; hover:background:#f8f9fa;"
                         onmouseover="this.style.background='#f8f9fa'" 
                         onmouseout="this.style.background='white'">
                        <div style="font-weight: bold;">${product.brand} ${product.model}</div>
                        <div style="font-size: 12px; color: #666;">
                            Code: ${product.code} | Stock: ${product.stock} | 
                            ${product.barcode ? 'Barcode: ' + product.barcode : ''}
                            ${product.imei ? 'IMEI: ' + product.imei : ''}
                        </div>
                        <div style="font-size: 12px; color: #28a745;">
                            Min: ${(product.min_selling_price || 0).toFixed(2)} EGP | 
                            Suggested: ${(product.suggested_price || product.price || 0).toFixed(2)} EGP
                        </div>
                    </div>
                `).join('');
            }
            
            resultsDiv.style.display = 'block';
        }

        function selectProduct(productOrId) {
            // Handle both product object and product ID
            if (typeof productOrId === 'object') {
                selectedProduct = productOrId;
            } else {
                selectedProduct = allProducts.find(p => p.id === productOrId);
            }
            
            if (selectedProduct) {
                document.getElementById('selected-product').innerHTML = `
                    <div style="color: #333;">
                        <strong>${selectedProduct.brand} ${selectedProduct.model}</strong><br>
                        <small>Code: ${selectedProduct.code} | Min: ${(selectedProduct.min_selling_price || 0).toFixed(2)} EGP | Suggested: ${(selectedProduct.suggested_price || selectedProduct.price || 0).toFixed(2)} EGP | Stock: ${selectedProduct.stock}</small>
                        ${selectedProduct.barcode ? '<br><small>Barcode: ' + selectedProduct.barcode + '</small>' : ''}
                        ${selectedProduct.imei ? '<br><small>IMEI: ' + selectedProduct.imei + '</small>' : ''}
                    </div>
                `;
                
                // Set the suggested price in the price input
                document.getElementById('selling-price').value = (selectedProduct.suggested_price || selectedProduct.price || selectedProduct.min_selling_price || 0).toFixed(2);
                document.getElementById('selling-price').min = selectedProduct.min_selling_price || 0;
                
                document.getElementById('add-product-btn').disabled = false;
                document.getElementById('product-search-results').style.display = 'none';
                document.getElementById('product-search').value = `${selectedProduct.brand} ${selectedProduct.model}`;
            }
        }

        function selectFirstProduct() {
            const resultsDiv = document.getElementById('product-search-results');
            const firstResult = resultsDiv.querySelector('div[onclick]');
            if (firstResult) {
                firstResult.click();
            }
        }

        // Add to receipt
        function addToReceipt() {
            console.log('=== ADD TO RECEIPT ===');
            console.log('Selected product:', selectedProduct);
            console.log('Current receipt items:', currentReceipt.items);
            
            const quantityInput = document.getElementById('quantity');
            const sellingPriceInput = document.getElementById('selling-price');
            
            console.log('Quantity input:', quantityInput.value);
            console.log('Selling price input:', sellingPriceInput.value);

            if (!selectedProduct) {
                alert('Please select a product');
                return;
            }

            const quantity = parseInt(quantityInput.value);
            const sellingPrice = parseFloat(sellingPriceInput.value);
            
            console.log('Parsed quantity:', quantity);
            console.log('Parsed selling price:', sellingPrice);

            if (!quantity || quantity <= 0) {
                alert('Please enter a valid quantity');
                return;
            }

            if (!sellingPrice || sellingPrice <= 0) {
                alert('Please enter a valid selling price');
                return;
            }

            if (sellingPrice < selectedProduct.min_selling_price) {
                alert(`Selling price cannot be less than minimum price: ${selectedProduct.min_selling_price.toFixed(2)} EGP `);
                return;
            }

            if (quantity > selectedProduct.stock) {
                alert(`Only ${selectedProduct.stock} items available in stock`);
                return;
            }

            console.log('All validations passed, adding to receipt...');

            // Check if item exists
            const existingItem = currentReceipt.items.find(item => item.productId === selectedProduct.id);

            if (existingItem) {
                if (existingItem.quantity + quantity <= selectedProduct.stock) {
                    existingItem.quantity += quantity;
                    existingItem.price = sellingPrice; // Update price to current selling price
                    existingItem.total = existingItem.quantity * existingItem.price;
                } else {
                    alert('Cannot exceed available stock');
                    return;
                }
            } else {
                currentReceipt.items.push({
                    productId: selectedProduct.id,
                    code: selectedProduct.code,
                    name: `${selectedProduct.brand} ${selectedProduct.model}`,
                    price: sellingPrice,
                    quantity: quantity,
                    total: sellingPrice * quantity
                });
            }

            console.log('Item added, new receipt items:', currentReceipt.items);
            updateReceiptDisplay();
            quantityInput.value = 1;

            // Clear selection
            selectedProduct = null;
            document.getElementById('selected-product').innerHTML = '<div style="color: #666;">No product selected</div>';
            document.getElementById('add-product-btn').disabled = true;
            document.getElementById('product-search').value = '';
            document.getElementById('selling-price').value = '';
        }

        // Update receipt display
        function updateReceiptDisplay() {
            const itemsDiv = document.getElementById('receipt-items');

            if (currentReceipt.items.length === 0) {
                itemsDiv.innerHTML = '<div class="no-data">No items added yet</div>';
            } else {
                itemsDiv.innerHTML = currentReceipt.items.map(item => `
                    <div class="receipt-item">
                        <div>
                            <strong>${item.name}</strong><br>
                            <small>Code: ${item.code}</small>
                        </div>
                        <div style="text-align: right;">
                            <div>${item.quantity} × ${item.price.toFixed(2)} EGP </div>
                            <div><strong>${item.total.toFixed(2)} EGP </strong></div>
                        </div>
                        <button class="btn btn-danger" onclick="removeFromReceipt(${item.productId})" style="padding: 5px 10px;">×</button>
                    </div>
                `).join('');
            }

            // Calculate totals
            let total = 0;
            currentReceipt.items.forEach(item => {
                total += item.total;
            });
            currentReceipt.total = total;
            
            console.log('Calculated total:', total);
            document.getElementById('total').textContent = total.toFixed(2) + ' EGP';
            
            // Update payment totals
            updatePaymentTotals();
            
            // Enable/disable complete button
            document.getElementById('complete-btn').disabled = currentReceipt.items.length === 0;
        }

        function removeFromReceipt(productId) {
            currentReceipt.items = currentReceipt.items.filter(item => item.productId !== productId);
            updateReceiptDisplay();
        }

        // Clear receipt
        function clearReceipt() {
            currentReceipt = { items: [], total: 0 };
            selectedProduct = null;
            updateReceiptDisplay();
            document.getElementById('customer-name').value = '';
            
            // Clear payment splits
            const container = document.getElementById('splitPaymentContainer');
            container.innerHTML = `
                <div class="payment-method-row" data-payment-row="0">
                    <select class="payment-method-select" style="width: 120px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; margin-right: 10px;">
                        <option value="">Select Method</option>
                        <option value="Cash">Cash</option>
                        <option value="Visa">Visa</option>
                        <option value="Instapay">Instapay</option>
                        <option value="Installment">Installment</option>
                    </select>
                    <input type="number" class="payment-amount" placeholder="Amount" style="width: 100px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; margin-right: 10px;" step="0.01" min="0">
                    <input type="text" class="payment-reference" placeholder="Reference (optional)" style="width: 150px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; margin-right: 10px;">
                    <button class="btn btn-sm btn-danger" onclick="removePaymentRow(0)" style="display: none;">×</button>
                </div>
            `;
            
            // Re-add event listeners
            const firstRow = document.querySelector('.payment-method-row');
            firstRow.querySelector('.payment-method-select').addEventListener('change', updatePaymentTotals);
            firstRow.querySelector('.payment-amount').addEventListener('input', updatePaymentTotals);
            
            paymentRowCount = 1;
            updatePaymentTotals();
            
            // Clear product selection
            document.getElementById('customer-phone').value = '';
            document.getElementById('product-search').value = '';
            document.getElementById('selected-product').innerHTML = '<div style="color: #666;">No product selected</div>';
            document.getElementById('add-product-btn').disabled = true;
            document.getElementById('product-search-results').style.display = 'none';
            document.getElementById('selling-price').value = '';
        }

        // Complete receipt
        async function completeReceipt() {
            const customerName = document.getElementById('customer-name').value.trim();

            if (!customerName) {
                alert('Please enter customer name');
                return;
            }

            const customerPhone = document.getElementById('customer-phone').value.trim();
            // Validate phone number format (Egyptian format: 11 digits starting with 01)
            const phoneRegex = /^01[0-2,5]{1}[0-9]{8}$/;
            if (customerPhone && !phoneRegex.test(customerPhone)) {
                const errorMsg = typeof langManager !== 'undefined' ? 
                    langManager.translate('common', 'invalidPhone') : 
                    'Please enter a valid 11-digit phone number (e.g., 01xxxxxxxxx)';
                alert(errorMsg);
                return;
            }

            // Check if user is logged in
            const staffId = <?php echo isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 'null'; ?>;
            if (!staffId) {
                alert('You are not logged in. Please refresh the page and login again.');
                return;
            }

            if (currentReceipt.items.length === 0) {
                alert('Please add items to receipt');
                return;
            }

            const completeBtn = document.getElementById('complete-btn');
            completeBtn.innerHTML = 'Processing...';
            completeBtn.disabled = true;

            try {
                // Try to add customer, but don't fail if it doesn't work
                let customerId = null;
                const customerPhone = document.getElementById('customer-phone').value.trim();

                if (customerPhone) {
                    try {
                        const customerResponse = await fetch('api/customers.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ name: customerName, phone: customerPhone })
                        });

                        const customerResult = await customerResponse.json();
                        if (customerResult.success) {
                            customerId = customerResult.customer_id;
                        }
                    } catch (customerError) {
                        console.warn('Customer creation failed, continuing without customer ID:', customerError);
                        // Continue with sale even if customer creation fails
                    }
                }

                // Create sale
                const paymentSplits = getPaymentSplits();
                
                if (paymentSplits.length === 0) {
                    alert('Please add at least one payment method');
                    return;
                }
                
                const saleData = {
                    customer_id: customerId,
                    staff_id: <?php echo isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 'null'; ?>,
                    subtotal: currentReceipt.total,
                    tax_amount: 0,
                    total_amount: currentReceipt.total,
                    payment_splits: paymentSplits,
                    is_split_payment: paymentSplits.length > 1,
                    staff_name: <?php echo json_encode(isset($_SESSION['name']) ? $_SESSION['name'] : 'Staff'); ?>,
                    staff_username: <?php echo json_encode(isset($_SESSION['username']) ? $_SESSION['username'] : 'staff'); ?>,
                    items: currentReceipt.items.map(item => ({
                        product_id: item.productId,
                        quantity: item.quantity,
                        unit_price: item.price,
                        total_price: item.total
                    }))
                };

                console.log('Sending sale data:', saleData);

                const saleResponse = await fetch('api/sales.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(saleData)
                });

                console.log('Sale response status:', saleResponse.status);

                if (!saleResponse.ok) {
                    let errorText = '';
                    try {
                        errorText = await saleResponse.text();
                        // Try to parse as JSON
                        try {
                            const errorJson = JSON.parse(errorText);
                            alert('Server error: ' + (errorJson.message || errorText));
                        } catch {
                            alert('Server error: ' + saleResponse.status + ' - ' + (errorText.substring(0, 200) || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Server error: ' + saleResponse.status + ' - Failed to read error message');
                    }
                    return;
                }

                const responseText = await saleResponse.text();
                console.log('Raw response:', responseText);

                let saleResult;
                try {
                    const jsonStart = responseText.indexOf('{');
                    const cleanText = jsonStart >= 0 ? responseText.substring(jsonStart) : responseText;
                    saleResult = JSON.parse(cleanText);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', responseText);
                    const receiptMatch = responseText.match(/RCP-\d{4}-\d+/);
                    if (receiptMatch) {
                        alert('Sale completed! Receipt #' + receiptMatch[0]);
                        clearReceipt();
                        loadProducts();
                        loadInventory();
                    } else {
                        alert('Sale was likely saved. Please refresh to confirm.');
                        clearReceipt();
                        loadProducts();
                    }
                    return;
                }
                console.log('Sale result:', saleResult);

                if (saleResult.success) {
                    printReceipt({
                        receipt_number: saleResult.receipt_number,
                        customer: customerName,
                        phone: customerPhone,
                        items: currentReceipt.items,
                        total: currentReceipt.total,
                        date: new Date(),
                        staff: <?php echo json_encode(isset($_SESSION['name']) ? $_SESSION['name'] : 'Staff'); ?>
                    });

                    alert('Sale completed successfully! Receipt #' + saleResult.receipt_number);
                    clearReceipt();
                    loadProducts(); // Refresh products to update stock
                    loadInventory(); // Refresh inventory
                } else {
                    alert('Failed to complete sale: ' + saleResult.message);
                }
            } catch (error) {
                console.error('Error completing sale:', error);
                alert('Error completing sale: ' + error.message);
            } finally {
                completeBtn.innerHTML = 'Complete Sale';
                completeBtn.disabled = false;
            }
        }

        // Print receipt
        function printReceipt(sale) {
            const receiptWindow = window.open('', '_blank', 'width=400,height=600');
            receiptWindow.document.write(`
                <html>
                <head>
                    <title>Receipt - ${sale.receipt_number}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .header { text-align: center; margin-bottom: 20px; }
                        .receipt-info { margin: 15px 0; }
                        .items { margin: 20px 0; }
                        .item { display: flex; justify-content: space-between; margin: 5px 0; }
                        .totals { border-top: 2px solid #000; padding-top: 10px; margin-top: 20px; }
                        .total-line { display: flex; justify-content: space-between; margin: 5px 0; }
                        .final-total { font-weight: bold; font-size: 1.2em; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>IBS Mobile Shop</h2>
                        <p>Receipt #${sale.receipt_number}</p>
                    </div>
                    
                    <div class="receipt-info">
                        <p><strong>Date:</strong> ${sale.date.toLocaleDateString()}</p>
                        <p><strong>Staff:</strong> ${sale.staff}</p>
                        <p><strong>Customer:</strong> ${sale.customer}</p>
                        ${sale.phone ? `<p><strong>Phone:</strong> ${sale.phone}</p>` : ''}
                    </div>
                    
                    <div class="items">
                        <h3>Items:</h3>
                        ${(() => {
                            const grouped = {};
                            sale.items.forEach(item => {
                                const key = item.productId + '_' + item.price;
                                if (!grouped[key]) {
                                    grouped[key] = { ...item };
                                } else {
                                    grouped[key].quantity += item.quantity;
                                    grouped[key].total += item.total;
                                }
                            });
                            return Object.values(grouped).map(item => `
                                <div class="item">
                                    <span>${item.name} (${item.code})</span>
                                    <span>${item.quantity} x ${item.price.toFixed(2)} EGP = ${item.total.toFixed(2)} EGP </span>
                                </div>
                            `).join('');
                        })()}
                    </div>
                    
                    <div class="totals">
                        <div class="total-line final-total">
                            <span>Total:</span>
                            <span>${sale.total.toFixed(2)} EGP </span>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <p>Thank you for your business!</p>
                    </div>
                </body>
                </html>
            `);
            receiptWindow.document.close();
            receiptWindow.print();
        }

        // Load inventory
        async function loadInventory() {
            try {
                const response = await fetch('api/products.php');
                const result = await response.json();

                if (result.success) {
                    allInventoryData = result.data; // Store all inventory data for filtering
                    displayInventory(result.data);
                    displayInventoryStats(result.data);

                    // Initialize search results count
                    const resultsCountDiv = document.getElementById('inventory-search-results-count');
                    if (resultsCountDiv) {
                        resultsCountDiv.textContent = `Showing all ${result.data.length} products`;
                        resultsCountDiv.style.color = '#666';
                    }
                }
            } catch (error) {
                console.error('Error loading inventory:', error);
            }
        }

        // Display inventory
        function displayInventory(products) {
            const tbody = document.getElementById('inventory-tbody');

            if (products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="no-data">No products found</td></tr>';
                return;
            }

            tbody.innerHTML = products.map(product => `
                <tr>
                    <td>${product.code}</td>
                    <td>${product.brand} ${product.model}</td>
                    <td>${(product.min_selling_price || 0).toFixed(2)} EGP </td>
                    <td>${(product.suggested_price || product.price || 0).toFixed(2)} EGP </td>
                    <td>${product.stock}</td>
                    <td>
                        <span class="${product.stock <= product.min_stock ? 'stock-low' : 'stock-ok'}">
                            ${product.stock <= product.min_stock ? '⚠️ Low Stock' : '✅ In Stock'}
                        </span>
                    </td>
                </tr>
            `).join('');
        }

        // Display inventory stats
        function displayInventoryStats(products) {
            const totalProducts = products.length;
            const lowStock = products.filter(p => p.stock <= p.min_stock).length;
            const outOfStock = products.filter(p => p.stock === 0).length;
            const totalValue = products.reduce((sum, p) => sum + (p.price * p.stock), 0);

            document.getElementById('inventory-stats').innerHTML = `
                <div class="stat-card">
                    <h3>${totalProducts}</h3>
                    <p>Total Products</p>
                </div>
                <div class="stat-card">
                    <h3>${lowStock}</h3>
                    <p>Low Stock Items</p>
                </div>
                <div class="stat-card">
                    <h3>${outOfStock}</h3>
                    <p>Out of Stock</p>
                </div>
                <div class="stat-card">
                    <h3> 0 EGP </h3>
                    <p>Inventory Value</p>
                </div>
            `;
        }

        // Inventory search functions
        function filterInventory(searchTerm) {
            const resultsCountDiv = document.getElementById('inventory-search-results-count');

            if (!searchTerm.trim()) {
                // If search is empty, show all products
                displayInventory(allInventoryData);
                displayInventoryStats(allInventoryData);
                resultsCountDiv.textContent = `Showing all ${allInventoryData.length} products`;
                resultsCountDiv.style.color = '#666';
                return;
            }

            const searchLower = searchTerm.toLowerCase();
            const filteredProducts = allInventoryData.filter(product => {
                return product.code.toLowerCase().includes(searchLower) ||
                    product.brand.toLowerCase().includes(searchLower) ||
                    product.model.toLowerCase().includes(searchLower) ||
                    (product.description && product.description.toLowerCase().includes(searchLower));
            });

            displayInventory(filteredProducts);
            displayInventoryStats(filteredProducts);

            // Update results count
            if (filteredProducts.length === 0) {
                resultsCountDiv.textContent = 'No products found matching your search';
                resultsCountDiv.style.color = '#dc3545';
            } else {
                resultsCountDiv.textContent = `Found ${filteredProducts.length} product${filteredProducts.length === 1 ? '' : 's'} matching "${searchTerm}"`;
                resultsCountDiv.style.color = '#28a745';
            }
        }

        function clearInventorySearch() {
            const searchInput = document.getElementById('inventorySearchInput');
            const resultsCountDiv = document.getElementById('inventory-search-results-count');

            searchInput.value = '';
            displayInventory(allInventoryData);
            displayInventoryStats(allInventoryData);
            resultsCountDiv.textContent = `Showing all ${allInventoryData.length} products`;
            resultsCountDiv.style.color = '#666';
        }
    </script>
    
    <script>
        // Language toggle function using the translation system
        function toggleLanguage() {
            langManager.toggleLanguage();
        }
        
        // Apply initial language when page loads
        document.addEventListener('DOMContentLoaded', function() {
            langManager.init();
        });
    </script>
</body>

</html>