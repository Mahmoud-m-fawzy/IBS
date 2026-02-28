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
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
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
            <h1>📱 <span data-translate="navigation.dashboard">IBS Staff Dashboard</span></h1>
        </div>
        <div class="user-info">
            <span data-translate="navigation.welcome">Welcome</span>,<?php echo $_SESSION['name']; ?>
            <a href="logout.php" class="logout-btn">🚪 <span data-translate="navigation.logout">Logout</span></a>
        </div>
    </div>

    <div class="nav-tabs">
        <button class="nav-tab active" onclick="showTab('receipt')">🧾 <span data-translate="sales.receipt">Receipt</span></button>
        <button class="nav-tab" onclick="showTab('inventory')">📦 <span data-translate="navigation.inventory">Inventory</span></button>
    </div>

    <div class="content">
        <?php include 'views/staff/staff_receipt_tab.php'; ?>

        <?php include 'views/staff/staff_inventory_tab.php'; ?>
    </div>

    <script>
        // Global variables
        let paymentRowCount = 1;
        let selectedProduct = null;
        let allProducts = [];
        let allInventoryProducts = [];
        let currentReceipt = { items: [], total: 0 };

        // Format currency to hide .00 for whole numbers
        function formatCurrency(amount) {
            const num = parseFloat(amount || 0);
            return num.toLocaleString('en-US', {
                minimumFractionDigits: num % 1 === 0 ? 0 : 2,
                maximumFractionDigits: 2
            });
        }

        // Price Formatting with Comma Separators
        function formatPriceInput(input) {
            let value = input.value.replace(/,/g, '');
            if (isNaN(value) || value === '') {
                input.value = '';
                const hiddenPrice = document.getElementById('selling-price');
                if (hiddenPrice) hiddenPrice.value = '';
                return;
            }
            const hiddenPrice = document.getElementById('selling-price');
            if (hiddenPrice) hiddenPrice.value = value;
            let parts = value.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            input.value = parts.join('.');
        }

        // Focus barcode scanner input
        function startBarcodeScan() {
            const input = document.getElementById('product-search');
            if (input) {
                input.focus();
                input.value = '';
                input.style.boxShadow = '0 0 0 4px rgba(0, 86, 179, 0.2)';
                setTimeout(() => input.style.boxShadow = '', 1000);
            }
        }

        function addPaymentRow() {
            const container = document.getElementById('splitPaymentContainer');
            const newRow = document.createElement('div');
            newRow.className = 'payment-row-modern';
            newRow.setAttribute('data-payment-row', paymentRowCount);

            const methodText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'method') : 'Method';
            const cashText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'cash') : 'Cash';
            const cardText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'card') : 'Visa';
            const amountPlaceholder = typeof langManager !== 'undefined' ? langManager.translate('sales', 'amount') : 'Amount';
            const refPlaceholder = typeof langManager !== 'undefined' ? langManager.translate('sales', 'reference') : 'Ref (optional)';

            newRow.innerHTML = `
                <select class="modern-input payment-method-select" onchange="updatePaymentTotals()">
                    <option value="">${methodText}</option>
                    <option value="Cash">${cashText}</option>
                    <option value="Visa">${cardText}</option>
                    <option value="Instapay">Instapay</option>
                    <option value="Installment">Installment</option>
                </select>
                <input type="number" class="modern-input payment-amount" placeholder="${amountPlaceholder}" step="0.01" min="0" oninput="updatePaymentTotals()">
                <input type="text" class="modern-input payment-reference" placeholder="${refPlaceholder}">
                <button class="btn-modern btn-danger-modern" onclick="removePaymentRow(${paymentRowCount})" style="padding: 8px 12px;">×</button>
            `;

            container.appendChild(newRow);
            if (typeof langManager !== 'undefined') langManager.applyLanguage(langManager.currentLang);
            paymentRowCount++;
            updatePaymentTotals();
        }

        function removePaymentRow(rowId) {
            const row = document.querySelector(`[data-payment-row="${rowId}"]`);
            if (row) { row.remove(); updatePaymentTotals(); }
        }

        function updatePaymentTotals() {
            const totalAmount = parseFloat(currentReceipt.total) || 0;
            let totalPaid = 0;

            document.querySelectorAll('.payment-row-modern').forEach(row => {
                const amountInput = row.querySelector('.payment-amount');
                const amount = parseFloat(amountInput.value) || 0;
                const method = row.querySelector('.payment-method-select').value;
                if (method && amount > 0) totalPaid += amount;
            });

            const remaining = totalAmount - totalPaid;
            document.getElementById('totalPaid').textContent = formatCurrency(totalPaid);
            document.getElementById('remainingAmount').textContent = formatCurrency(remaining);

            const remainingElement = document.getElementById('remainingAmount');
            if (Math.abs(remaining) < 0.01) {
                remainingElement.style.color = 'var(--primary-green)';
            } else if (remaining > 0) {
                remainingElement.style.color = 'var(--primary-red)';
            } else {
                remainingElement.style.color = 'var(--secondary-yellow)';
            }

            const completeBtn = document.getElementById('complete-btn');
            if (completeBtn) {
                const isBalanced = Math.abs(remaining) < 0.01;
                const hasItems = currentReceipt.items.length > 0;
                completeBtn.disabled = !hasItems || !isBalanced;
                if (completeBtn.disabled) {
                    completeBtn.style.opacity = '0.5';
                    completeBtn.style.cursor = 'not-allowed';
                    completeBtn.title = !hasItems ? 'Add items first' : 'Remaining balance must be 0.00';
                } else {
                    completeBtn.style.opacity = '1';
                    completeBtn.style.cursor = 'pointer';
                    completeBtn.title = 'Complete Sale';
                }
            }
        }

        function getPaymentSplits() {
            const splits = [];
            document.querySelectorAll('.payment-row-modern').forEach(row => {
                const method = row.querySelector('.payment-method-select').value;
                const amount = parseFloat(row.querySelector('.payment-amount').value) || 0;
                const reference = row.querySelector('.payment-reference').value || null;
                if (method && amount > 0) splits.push({ payment_method: method, amount, reference_number: reference });
            });
            return splits;
        }

        // ---- Product Search & Barcode ----
        let scanTimeout = null;
        let isScanning = false;

        function handleProductInput(value) {
            if (!allProducts || allProducts.length === 0) return;
            if (/^\d+$/.test(value) && value.length >= 8) {
                if (!isScanning) {
                    isScanning = true;
                    const feedback = document.getElementById('scan-feedback');
                    const indicator = document.getElementById('scan-indicator');
                    if (feedback) feedback.style.display = 'block';
                    if (indicator) indicator.textContent = '🔄';
                }
                if (scanTimeout) clearTimeout(scanTimeout);
                scanTimeout = setTimeout(() => {
                    processBarcode(value);
                    isScanning = false;
                    const fb = document.getElementById('scan-feedback');
                    const ind = document.getElementById('scan-indicator');
                    if (fb) fb.style.display = 'none';
                    if (ind) ind.textContent = '📷';
                }, 500);
            } else {
                if (scanTimeout) clearTimeout(scanTimeout);
                isScanning = false;
                const feedback = document.getElementById('scan-feedback');
                const indicator = document.getElementById('scan-indicator');
                if (feedback) feedback.style.display = 'none';
                if (indicator) indicator.textContent = '📷';
            }
        }

        function processBarcode(barcode) {
            let product = allProducts.find(p => p.barcode === barcode || p.imei === barcode);
            if (!product) product = allProducts.find(p => p.code === barcode);
            if (!product) product = allProducts.find(p =>
                p.code.includes(barcode) ||
                (p.barcode && p.barcode.includes(barcode)) ||
                (p.imei && p.imei.includes(barcode))
            );
            if (product) {
                selectProduct(product);
                const inp = document.getElementById('product-search');
                if (inp) inp.value = '';
                showScanFeedback('✅ Product found: ' + product.brand + ' ' + product.model, 'success');
            } else {
                showScanFeedback('❌ No product found for barcode: ' + barcode, 'error');
                setTimeout(() => {
                    const inp = document.getElementById('product-search');
                    if (inp) inp.value = '';
                }, 2000);
            }
        }

        function showScanFeedback(message, type) {
            const feedback = document.getElementById('scan-feedback');
            if (feedback) {
                feedback.textContent = message;
                feedback.style.display = 'block';
                feedback.style.color = type === 'success' ? '#28a745' : '#dc3545';
            }
            setTimeout(() => { if (feedback) feedback.style.display = 'none'; }, 3000);
        }

        function searchProducts(searchTerm) {
            let resultsDiv = document.getElementById('receipt-product-search-results');
            if (!resultsDiv) { console.error('Results container not found'); return; }

            if (!searchTerm.trim()) { resultsDiv.style.display = 'none'; return; }

            const searchLower = searchTerm.toLowerCase();
            const filteredProducts = allProducts.filter(product =>
                product.code.toLowerCase().includes(searchLower) ||
                product.brand.toLowerCase().includes(searchLower) ||
                product.model.toLowerCase().includes(searchLower) ||
                `${product.brand} ${product.model}`.toLowerCase().includes(searchLower) ||
                (product.barcode && product.barcode.includes(searchLower)) ||
                (product.imei && product.imei.includes(searchLower))
            );

            if (filteredProducts.length === 0) {
                resultsDiv.innerHTML = '<div style="padding: 15px; color: var(--gray-500); text-align: center;">No products found</div>';
            } else {
                resultsDiv.innerHTML = filteredProducts.map(product => {
                    const stock = product.available_stock || 0;
                    let stockClass = 'stock-in';
                    if (stock <= 0) stockClass = 'stock-out';
                    else if (stock <= 5) stockClass = 'stock-low';
                    const imgUrl = product.image_url || 'components/css/logo.jpeg';
                    return `
                        <div class="product-item-modern" onclick="selectProduct(${JSON.stringify(product).replace(/"/g, '&quot;')})">
                            <img src="${imgUrl}" class="product-img" onerror="this.src='components/css/logo.jpeg'">
                            <div class="info">
                                <span class="name">${product.brand} ${product.model}</span>
                                <span class="code">${product.code}</span>
                            </div>
                            <div style="text-align: right;">
                                <div class="stock-badge ${stockClass}">${stock} IN STOCK</div>
                                <div class="price-tag">${formatCurrency(product.suggested_price || product.price || 0)} EGP</div>
                            </div>
                        </div>
                    `;
                }).join('');
            }
            resultsDiv.style.display = 'block';
        }

        function selectProduct(productOrId) {
            if (typeof productOrId === 'object') {
                selectedProduct = productOrId;
            } else {
                selectedProduct = allProducts.find(p => p.id === productOrId);
            }
            if (selectedProduct) {
                document.getElementById('selected-product').innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <span><strong>${selectedProduct.brand} ${selectedProduct.model}</strong> (${selectedProduct.code})</span>
                        <span style="color: var(--primary-green); font-weight: 700;">Stock: ${selectedProduct.available_stock || 0}</span>
                    </div>
                `;
                document.getElementById('product-search').focus();
                const priceValue = (selectedProduct.suggested_price || selectedProduct.price || selectedProduct.min_selling_price || 0).toFixed(2);
                document.getElementById('selling-price').value = priceValue;
                const displayInput = document.getElementById('selling-price-display');
                displayInput.value = priceValue;
                formatPriceInput(displayInput);
                document.getElementById('add-product-btn').disabled = false;
                const resultsDiv = document.getElementById('receipt-product-search-results');
                if (resultsDiv) resultsDiv.style.display = 'none';
                document.getElementById('product-search').value = '';
            }
        }

        function selectFirstProduct() {
            const resultsDiv = document.getElementById('receipt-product-search-results');
            if (resultsDiv && resultsDiv.style.display !== 'none') {
                const firstResult = resultsDiv.querySelector('.product-item-modern, div[onclick]');
                if (firstResult) firstResult.click();
            }
        }

        function testSearch() {
            console.log('All products:', allProducts.length);
            if (allProducts && allProducts.length > 0) {
                searchProducts('test');
            } else {
                loadProducts().then(() => console.log('Products loaded:', allProducts.length));
            }
        }

        // ---- Item Selection Modal ----
        function showItemSelectionModal() {
            if (!selectedProduct) { alert('Please select a product first'); return; }
            const sellingPriceInput = document.getElementById('selling-price');
            if (!sellingPriceInput.value) { alert('Please enter a selling price'); return; }

            document.getElementById('selected-product-info').innerHTML = `
                <strong>${selectedProduct.brand} ${selectedProduct.model}</strong><br>
                Code: ${selectedProduct.code}<br>
                Available Stock: ${selectedProduct.available_stock}
            `;
            document.getElementById('modal-selling-price').value = sellingPriceInput.value;
            const quantityInput = document.getElementById('quantity');
            document.getElementById('modal-quantity').value = quantityInput.value;
            document.getElementById('modal-quantity').max = selectedProduct.available_stock;
            loadAvailableItems();
            document.getElementById('item-selection-modal').style.display = 'block';
        }

        function closeItemSelectionModal() {
            document.getElementById('item-selection-modal').style.display = 'none';
        }

        function updateItemSelection() {
            const quantity = parseInt(document.getElementById('modal-quantity').value);
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');

            if (checkedBoxes.length > quantity) {
                const allChecked = Array.from(checkboxes).filter(cb => cb.checked);
                const lastChecked = allChecked[allChecked.length - 1];
                if (lastChecked) {
                    lastChecked.checked = false;
                    const addButton = document.getElementById('modal-add-btn');
                    const originalText = addButton.textContent;
                    addButton.textContent = `Cannot select more than ${quantity} items!`;
                    addButton.style.background = 'var(--danger-color)';
                    setTimeout(() => { addButton.textContent = originalText; addButton.style.background = ''; }, 2000);
                }
            }

            const selectAllCheckbox = document.getElementById('select-all-items');
            if (selectAllCheckbox) selectAllCheckbox.checked = (checkedBoxes.length === quantity && quantity === checkboxes.length);

            const addButton = document.getElementById('modal-add-btn');
            if (addButton) {
                const currentChecked = document.querySelectorAll('.item-checkbox:checked').length;
                addButton.textContent = `Add Selected Items (${currentChecked}/${quantity})`;
                addButton.disabled = currentChecked !== quantity;
                addButton.style.opacity = currentChecked === quantity ? '1' : '0.6';
            }
        }

        async function loadAvailableItems() {
            try {
                const response = await fetch(`api/stock_items.php?product_id=${selectedProduct.id}`);
                const result = await response.json();
                if (result.success) {
                    displayAvailableItems(result.data);
                } else {
                    document.getElementById('available-items-list').innerHTML =
                        '<p style="color: red;">Error loading items: ' + result.message + '</p>';
                }
            } catch (error) {
                console.error('Error loading items:', error);
                document.getElementById('available-items-list').innerHTML = '<p style="color: red;">Error loading items</p>';
            }
        }

        function displayAvailableItems(items) {
            const container = document.getElementById('available-items-list');
            const quantity = parseInt(document.getElementById('modal-quantity').value);

            document.getElementById('selected-product-info').innerHTML = `
                <div>
                    <div style="font-weight: 800; font-size: 1.1em; color: var(--dark-blue);">${selectedProduct.brand} ${selectedProduct.model}</div>
                    <div style="font-size: 0.9em; color: var(--gray-600); font-family: var(--font-family-mono); margin-top: 4px;">Code: ${selectedProduct.code}</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.8em; color: var(--gray-500); text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Available Stock</div>
                    <div style="font-size: 1.5em; font-weight: 800; color: var(--primary-blue); line-height: 1;">${items.length} <span style="font-size: 0.5em; font-weight: 600;">UNITS</span></div>
                </div>
            `;

            if (quantity > items.length) {
                container.innerHTML = `<div style="padding: 20px; text-align: center; background: #fff5f5; border-radius: 10px; color: #e53e3e; border: 1px solid #feb2b2;">
                    <span style="font-size: 2em; display: block; margin-bottom: 10px;">⚠️</span>
                    Only <strong>${items.length}</strong> items available, but you requested <strong>${quantity}</strong>.
                </div>`;
                return;
            }

            container.innerHTML = `
                <div style="margin-bottom: 15px; padding: 12px 15px; background: rgba(0, 86, 179, 0.05); border-radius: 10px; color: var(--primary-blue); font-size: 0.9em; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(0, 86, 179, 0.1);">
                    <span id="modal-selection-count" style="font-weight: 600;">Select First ${quantity} items (of ${items.length} available)</span>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; background: white; padding: 5px 12px; border-radius: 8px; box-shadow: var(--shadow-xs);">
                        <input type="checkbox" id="select-all-items" onchange="toggleSelectAllItems(this)" style="width: 16px; height: 16px; accent-color: var(--primary-blue);">
                        Select All
                    </label>
                </div>
                <div style="max-height: 300px; overflow-y: auto; padding-right: 5px; margin-right: -5px;">
                    ${items.map((item, index) => `
                        <div class="item-checkbox-row" style="${index < quantity ? 'background: rgba(0, 86, 179, 0.03); border-color: var(--primary-blue);' : ''}">
                            <label style="display: flex; align-items: center; cursor: pointer; position: relative;">
                                <input type="checkbox" class="item-checkbox" value="${item.id}" data-item='${JSON.stringify(item)}'
                                       ${index < quantity ? 'checked' : ''} onchange="updateItemSelection()"
                                       style="width: 20px; height: 20px; accent-color: var(--primary-blue); margin-right: 15px;">
                                <div style="flex-grow: 1;">
                                    <div style="font-weight: 700; color: var(--dark-blue); font-size: 1.1em;">${item.item_code || 'UNIT-' + item.id}</div>
                                    <div style="display: flex; gap: 15px; margin-top: 4px;">
                                        ${item.imei ? `<span style="font-size: 11px; color: var(--gray-600); background: var(--gray-100); padding: 2px 6px; border-radius: 4px;">IMEI: ${item.imei}</span>` : ''}
                                        ${item.serial_number ? `<span style="font-size: 11px; color: var(--gray-600); background: var(--gray-100); padding: 2px 6px; border-radius: 4px;">SN: ${item.serial_number}</span>` : ''}
                                        ${item.color ? `<span style="font-size: 11px; color: var(--gray-600); background: var(--gray-100); padding: 2px 6px; border-radius: 4px; border-left: 3px solid ${item.color.toLowerCase()};">Color: ${item.color}</span>` : ''}
                                    </div>
                                </div>
                            </label>
                        </div>
                    `).join('')}
                </div>
            `;
            updateItemSelection();
        }

        function toggleSelectAllItems(checkbox) {
            const quantity = parseInt(document.getElementById('modal-quantity').value);
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach((cb, index) => { cb.checked = checkbox.checked && index < quantity; });
            updateItemSelection();
        }

        function addSelectedItemsToReceipt() {
            const selectedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
            const quantity = parseInt(document.getElementById('modal-quantity').value);

            if (selectedCheckboxes.length !== quantity) {
                alert(`Please select exactly ${quantity} items (currently selected: ${selectedCheckboxes.length})`);
                return;
            }

            const sellingPrice = parseFloat(document.getElementById('modal-selling-price').value);
            selectedCheckboxes.forEach(checkbox => {
                const item = JSON.parse(checkbox.dataset.item);
                currentReceipt.items.push({
                    productId: selectedProduct.id,
                    productItemId: item.id,
                    itemCode: item.item_code,
                    imei: item.imei,
                    serialNumber: item.serial_number,
                    name: `${selectedProduct.brand} ${selectedProduct.model}`,
                    price: sellingPrice,
                    quantity: 1,
                    total: sellingPrice
                });
            });

            updateReceiptDisplay();
            closeItemSelectionModal();

            selectedProduct = null;
            document.getElementById('selected-product').innerHTML = '<div style="color: #666;">No product selected</div>';
            document.getElementById('add-product-btn').disabled = true;
            document.getElementById('product-search').value = '';
            document.getElementById('selling-price').value = '';
            document.getElementById('quantity').value = 1;
        }

        function updateReceiptDisplay() {
            const itemsBody = document.getElementById('receipt-items-body');
            if (currentReceipt.items.length === 0) {
                itemsBody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--gray-400); padding: 40px;">No items added yet</td>
                    </tr>
                `;
            } else {
                const groupedItems = {};
                currentReceipt.items.forEach(item => {
                    const key = `${item.productId}_${item.price}`;
                    if (!groupedItems[key]) {
                        groupedItems[key] = { productId: item.productId, name: item.name, price: item.price, quantity: 0, total: 0, itemCodes: [], imeis: [], serialNumbers: [] };
                    }
                    groupedItems[key].quantity += item.quantity;
                    groupedItems[key].total += item.total;
                    if (item.itemCode) groupedItems[key].itemCodes.push(item.itemCode);
                    if (item.imei) groupedItems[key].imeis.push(item.imei);
                    if (item.serialNumber) groupedItems[key].serialNumbers.push(item.serialNumber);
                });

                itemsBody.innerHTML = Object.values(groupedItems).map(group => `
                    <tr>
                        <td>
                            <div style="font-weight: 600;">${group.name}</div>
                            ${group.itemCodes.length > 0 ? `<div style="font-size: 11px; color: var(--gray-500);">${group.itemCodes.map(c => `Code: ${c}`).join('<br>')}</div>` : ''}
                            ${group.imeis.length > 0 ? `<div style="font-size: 11px; color: var(--blue-600);">${group.imeis.map(i => `IMEI: ${i}`).join('<br>')}</div>` : ''}
                            ${group.serialNumbers.length > 0 ? `<div style="font-size: 11px; color: var(--blue-600);">${group.serialNumbers.map(s => `Serial: ${s}`).join('<br>')}</div>` : ''}
                        </td>
                        <td class="text-right">${formatCurrency(group.price)}</td>
                        <td class="text-right"><span style="font-weight: 600; padding: 5px 10px; background: var(--gray-100); border-radius: 3px;">${group.quantity}</span></td>
                        <td class="text-right" style="font-weight: 700;">${formatCurrency(group.total)}</td>
                        <td class="text-right"><button onclick="removeFromReceipt(${group.productId})" class="btn-modern btn-danger-modern" style="padding: 4px 8px; font-size: 12px;">×</button></td>
                    </tr>
                `).join('');
            }

            currentReceipt.total = currentReceipt.items.reduce((sum, item) => sum + item.total, 0);
            document.getElementById('total').textContent = formatCurrency(currentReceipt.total) + ' EGP';
            const completeBtn = document.getElementById('complete-btn');
            if (completeBtn) completeBtn.disabled = currentReceipt.items.length === 0;
            updatePaymentTotals();
        }

        function removeFromReceipt(productId) {
            currentReceipt.items = currentReceipt.items.filter(item => item.productId !== productId);
            updateReceiptDisplay();
        }

        function clearReceipt() {
            currentReceipt = { items: [], total: 0 };
            selectedProduct = null;
            updateReceiptDisplay();
            document.getElementById('customer-name').value = '';
            document.getElementById('customer-phone').value = '';
            const inp = document.getElementById('product-search');
            if (inp) inp.value = '';
            document.getElementById('selected-product').innerHTML = '<div style="color: #666;">No product selected</div>';
            document.getElementById('add-product-btn').disabled = true;
            const resultsDiv = document.getElementById('receipt-product-search-results');
            if (resultsDiv) resultsDiv.style.display = 'none';
            document.getElementById('selling-price').value = '';
            document.getElementById('quantity').value = '1';

            const container = document.getElementById('splitPaymentContainer');
            const methodText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'method') : 'Method';
            const cashText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'cash') : 'Cash';
            const cardText = typeof langManager !== 'undefined' ? langManager.translate('sales', 'card') : 'Visa';
            const amountPlaceholder = typeof langManager !== 'undefined' ? langManager.translate('sales', 'amount') : 'Amount';
            const refPlaceholder = typeof langManager !== 'undefined' ? langManager.translate('sales', 'reference') : 'Ref (optional)';

            container.innerHTML = `
                <div class="payment-row-modern" data-payment-row="0">
                    <select class="modern-input payment-method-select" onchange="updatePaymentTotals()">
                        <option value="">${methodText}</option>
                        <option value="Cash">${cashText}</option>
                        <option value="Visa">${cardText}</option>
                        <option value="Instapay">Instapay</option>
                        <option value="Installment">Installment</option>
                    </select>
                    <input type="number" class="modern-input payment-amount" placeholder="${amountPlaceholder}" step="0.01" min="0" oninput="updatePaymentTotals()">
                    <input type="text" class="modern-input payment-reference" placeholder="${refPlaceholder}">
                    <button class="btn-modern btn-danger-modern" onclick="removePaymentRow(0)" style="display: none; padding: 8px 12px;">×</button>
                </div>
            `;
            paymentRowCount = 1;
            if (typeof langManager !== 'undefined') langManager.applyLanguage(langManager.currentLang);
            updatePaymentTotals();
        }

        // ---- Complete Receipt / Sale ----
        async function completeReceipt() {
            const completeBtn = document.getElementById('complete-btn');
            completeBtn.innerHTML = 'Processing...';
            completeBtn.disabled = true;

            try {
                const customerName = document.getElementById('customer-name').value.trim();
                const customerPhone = document.getElementById('customer-phone').value.trim();

                if (!customerName) {
                    alert('Please enter customer name');
                    completeBtn.innerHTML = '✅ Complete Sale';
                    completeBtn.disabled = false;
                    return;
                }
                if (!customerPhone) {
                    alert('Customer phone is missing');
                    completeBtn.innerHTML = '✅ Complete Sale';
                    completeBtn.disabled = false;
                    return;
                }
                const phoneRegex = /^01[0-2,5]{1}[0-9]{8}$/;
                if (!phoneRegex.test(customerPhone)) {
                    alert('Please enter a valid 11-digit phone number (e.g., 01xxxxxxxxx)');
                    completeBtn.innerHTML = '✅ Complete Sale';
                    completeBtn.disabled = false;
                    return;
                }

                let customerId = null;
                try {
                    const customerResponse = await fetch('api/customers.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ name: customerName, phone: customerPhone })
                    });
                    const customerResult = await customerResponse.json();
                    if (customerResult.success) customerId = customerResult.customer_id;
                } catch (e) {
                    console.warn('Customer creation failed, continuing:', e);
                }

                const paymentSplits = getPaymentSplits();
                if (paymentSplits.length === 0) {
                    alert('Please add at least one payment method');
                    completeBtn.innerHTML = '✅ Complete Sale';
                    completeBtn.disabled = false;
                    return;
                }

                const saleData = {
                    customer_id: customerId,
                    customer_name: customerName,
                    customer_phone: customerPhone,
                    staff_id: <?php echo isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 'null'; ?>,
                    staff_name: <?php echo json_encode(isset($_SESSION['name']) ? $_SESSION['name'] : 'Staff'); ?>,
                    staff_username: <?php echo json_encode(isset($_SESSION['username']) ? $_SESSION['username'] : 'staff'); ?>,
                    total_amount: currentReceipt.total,
                    payment_splits: paymentSplits,
                    is_split_payment: paymentSplits.length > 1,
                    items: currentReceipt.items.map(item => {
                        const saleItem = {
                            product_id: item.productId,
                            quantity: item.quantity,
                            unit_price: item.price,
                            total_price: item.price * item.quantity
                        };
                        if (item.productItemId) saleItem.product_item_ids = [item.productItemId];
                        else if (item.itemCode) saleItem.item_codes = [item.itemCode];
                        return saleItem;
                    })
                };

                const saleResponse = await fetch('api/sales.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(saleData)
                });

                if (!saleResponse.ok) {
                    const errorText = await saleResponse.text();
                    try { const ej = JSON.parse(errorText); alert('Server error: ' + (ej.message || errorText)); }
                    catch { alert('Server error: ' + saleResponse.status); }
                    return;
                }

                const responseText = await saleResponse.text();
                let saleResult;
                try {
                    const jsonStart = responseText.indexOf('{');
                    saleResult = JSON.parse(jsonStart >= 0 ? responseText.substring(jsonStart) : responseText);
                } catch (e) {
                    const receiptMatch = responseText.match(/RCP-\d{4}-\d+/);
                    if (receiptMatch) { showSuccessModal(receiptMatch[0], currentReceipt.total); }
                    else { alert('Sale was likely saved. Please refresh to confirm.'); }
                    clearReceipt(); loadProducts();
                    return;
                }

                if (saleResult.success) {
                    showSuccessModal(saleResult.receipt_number, currentReceipt.total);

                    setTimeout(async () => {
                        try {
                            const receiptData = {
                                id: saleResult.sale_id,
                                receipt_number: saleResult.receipt_number,
                                sale_date: new Date().toISOString(),
                                staff_name: '<?php echo isset($_SESSION["name"]) ? $_SESSION["name"] : "Staff"; ?>',
                                customer_name: document.getElementById('customer-name').value || 'Walk-in Customer',
                                payment_method: 'Cash',
                                total_amount: currentReceipt.total,
                                items: currentReceipt.items.map(item => ({
                                    product_brand: item.name.split(' ')[0],
                                    product_model: item.name.split(' ').slice(1).join(' '),
                                    product_code: item.code,
                                    quantity: item.quantity,
                                    unit_price: item.price,
                                    total_price: item.price * item.quantity
                                }))
                            };
                            const printContent = generatePrintableReceipt(receiptData);
                            const printWindow = window.open('', '_blank', 'width=400,height=600');
                            if (printWindow) {
                                printWindow.document.write(`
                                    <html><head><title>🧾 Receipt - ${saleResult.receipt_number}</title>
                                    <style>
                                        body { font-family: Arial, sans-serif; padding: 20px; }
                                        .receipt-preview { border: 2px solid #ddd; padding: 10px; margin: 20px 0; background: white; transform: scale(0.8); transform-origin: top center; }
                                        .print-btn { background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; margin: 10px; font-size: 16px; font-weight: bold; }
                                        .cancel-btn { background: #6c757d; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; margin: 10px; font-size: 16px; }
                                        .button-group { text-align: center; margin: 20px 0; }
                                    </style></head>
                                    <body>
                                        <div style="text-align:center;"><h2>🧾 Receipt Ready</h2><p><strong>Receipt #:</strong> ${saleResult.receipt_number}</p></div>
                                        <div class="receipt-preview">${printContent}</div>
                                        <div class="button-group">
                                            <button class="print-btn" onclick="window.print(); window.close();">🖨️ Print Receipt</button>
                                            <button class="cancel-btn" onclick="window.close();">❌ Cancel</button>
                                        </div>
                                    </body></html>
                                `);
                                printWindow.document.close();
                            } else {
                                await printReceipt(saleResult.sale_id);
                            }
                            setTimeout(() => showSaleCompleteMessage(), 1000);
                        } catch (printError) {
                            console.warn('Auto-print failed:', printError);
                            await printReceipt(saleResult.sale_id);
                        }
                    }, 500);
                } else {
                    alert('Failed to complete sale: ' + saleResult.message);
                }
            } catch (error) {
                console.error('Error completing sale:', error);
                alert('Error completing sale: ' + error.message);
            } finally {
                const btn = document.getElementById('complete-btn');
                if (btn) { btn.innerHTML = '✅ Complete Sale'; updatePaymentTotals(); }
            }
        }

        // ---- Success Modal ----
        function showSuccessModal(receiptNumber, total) {
            const overlay = document.getElementById('successModalOverlay');
            const receiptEl = document.getElementById('successReceiptNumber');
            const totalEl = document.getElementById('successTotalAmount');
            if (receiptEl) receiptEl.textContent = receiptNumber;
            if (totalEl) totalEl.textContent = formatCurrency(total) + ' EGP';
            if (typeof langManager !== 'undefined') langManager.applyLanguage(langManager.currentLang);
            if (overlay) overlay.style.display = 'flex';
        }

        function closeSuccessModal() {
            const overlay = document.getElementById('successModalOverlay');
            if (overlay) overlay.style.display = 'none';
            clearReceipt();
            loadProducts();
            loadInventory();
        }

        function showSaleCompleteMessage() {
            const successDiv = document.createElement('div');
            successDiv.style.cssText = `position:fixed;top:20px;right:20px;background:#28a745;color:white;padding:15px 20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:10000;font-weight:600;display:flex;align-items:center;gap:10px;animation:slideIn 0.3s ease-out;`;
            successDiv.innerHTML = `<span style="font-size:20px;">✅</span><span>Sale completed! Ready for next sale.</span>`;
            const style = document.createElement('style');
            style.textContent = `@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}@keyframes slideOut{from{transform:translateX(0);opacity:1}to{transform:translateX(100%);opacity:0}}`;
            document.head.appendChild(style);
            document.body.appendChild(successDiv);
            setTimeout(() => {
                successDiv.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => { if (successDiv.parentNode) successDiv.parentNode.removeChild(successDiv); }, 300);
            }, 3000);
            setTimeout(() => {
                clearReceipt(); loadProducts(); loadInventory();
                const ps = document.getElementById('product-search');
                if (ps) ps.focus();
            }, 1000);
        }

        // ---- Receipt Printing ----
        async function printReceipt(saleId) {
            try {
                const response = await fetch(`api/sales.php?id=${saleId}`);
                const result = await response.json();
                if (!result.success) { alert('Failed to load receipt data for printing'); return; }
                const printWindow = window.open('', '_blank');
                if (!printWindow) { alert('Pop-up blocked! Please allow pop-ups to print receipts.'); return; }
                const printContent = generatePrintableReceipt(result.data);
                printWindow.document.write(printContent);
                printWindow.document.close();
                setTimeout(() => { printWindow.focus(); printWindow.print(); printWindow.close(); }, 500);
            } catch (error) {
                console.error('Error printing receipt:', error);
                alert('Error printing receipt. Please try again.');
            }
        }

        function generatePrintableReceipt(receiptData) {
            const currentDate = new Date(receiptData.sale_date).toLocaleString();
            const receiptNumber = receiptData.receipt_number;
            const receiptId = receiptData.id;
            const barcodeUrl = `https://barcode.tec-it.com/barcode.ashx?data=${encodeURIComponent(receiptNumber)}&code=Code128&multiplebarcodes=false&translate-esc=false&unit=Fit&dpi=96&imagetype=Gif&rotation=0&color=%23000000&bgcolor=%23ffffff`;

            // Group items by product+price
            const groupedItems = {};
            (receiptData.items || []).forEach(item => {
                const key = (item.product_id || item.product_name) + '_' + item.unit_price;
                if (!groupedItems[key]) {
                    groupedItems[key] = {
                        name: item.product_name || `${item.product_brand} ${item.product_model}`,
                        unit_price: item.unit_price,
                        quantity: 0,
                        total_price: 0,
                        imeis: [], serials: []
                    };
                }
                groupedItems[key].quantity += item.quantity;
                groupedItems[key].total_price += item.total_price;
                if (item.imei) groupedItems[key].imeis.push(...item.imei.split(', ').filter(Boolean));
                if (item.serial) groupedItems[key].serials.push(...item.serial.split(', ').filter(Boolean));
            });

            const itemsHtml = Object.values(groupedItems).map(item => `
                <tr>
                    <td class="item-name">
                        ${item.name}
                        ${item.imeis.length > 0 ? `<br><small>IMEI: ${item.imeis.join(', ')}</small>` : ''}
                        ${item.serials.length > 0 ? `<br><small>S/N: ${item.serials.join(', ')}</small>` : ''}
                    </td>
                    <td class="item-qty">${item.quantity}</td>
                    <td class="item-price">${parseFloat(item.unit_price).toLocaleString()}</td>
                    <td class="item-total">${parseFloat(item.total_price).toLocaleString()}</td>
                </tr>
            `).join('');

            const totalAmount = parseFloat(receiptData.total_amount || 0);

            return `<!DOCTYPE html><html><head><title>Receipt - ${receiptNumber}</title>
                <style>
                    @page { size: 80mm auto; margin: 5mm; }
                    body { font-family: 'Courier New', monospace; width: 80mm; margin: 0 auto; padding: 10px; font-size: 12px; line-height: 1.2; }
                    .header { text-align: center; margin-bottom: 15px; border-bottom: 2px dashed #000; padding-bottom: 10px; }
                    .company-name { font-size: 16px; font-weight: bold; margin-bottom: 5px; }
                    .company-address { font-size: 10px; margin-bottom: 5px; }
                    .receipt-info { margin-bottom: 15px; font-size: 11px; }
                    .receipt-info div { margin-bottom: 3px; }
                    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 10px; }
                    .items-table th, .items-table td { text-align: left; padding: 3px 0; vertical-align: top; }
                    .items-table th { border-bottom: 1px solid #000; font-weight: bold; font-size: 9px; }
                    .item-name { max-width: 45mm; word-wrap: break-word; }
                    .item-qty { text-align: center; width: 10mm; }
                    .item-price { text-align: right; width: 15mm; }
                    .item-total { text-align: right; width: 15mm; font-weight: bold; }
                    .total-section { border-top: 2px solid #000; padding-top: 10px; margin-top: 10px; }
                    .total-line { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px; }
                    .final-total { font-weight: bold; font-size: 14px; border-top: 1px solid #000; padding-top: 5px; margin-top: 8px; }
                    .barcode-section { text-align: center; margin: 15px 0; padding: 10px; border: 1px dashed #000; }
                    .barcode-image { max-width: 60mm; height: 20mm; margin: 5px auto; }
                    .footer { text-align: center; margin-top: 15px; border-top: 1px dashed #000; padding-top: 10px; font-size: 10px; }
                    @media print { body { width: auto; margin: 0; } }
                </style></head><body>
                <div class="header">
                    <div class="company-name">📱 IBS MOBILE SHOP</div>
                    <div class="company-address">Mobile &amp; Electronics Store</div>
                    <div class="company-address">📍 Egypt - Cairo</div>
                    <div class="company-address">📞 +20 123 456 7890</div>
                </div>
                <div class="receipt-info">
                    <div><strong>🧾 RECEIPT #:</strong> ${receiptNumber}</div>
                    <div><strong>📅 DATE:</strong> ${currentDate}</div>
                    <div><strong>👤 CUSTOMER:</strong> ${receiptData.customer_name || 'Walk-in'}</div>
                    <div><strong>👨‍💼 STAFF:</strong> ${receiptData.staff_name || 'Staff'}</div>
                </div>
                <table class="items-table">
                    <thead><tr><th class="item-name">Item</th><th class="item-qty">Qty</th><th class="item-price">Price</th><th class="item-total">Total</th></tr></thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
                <div class="total-section">
                    <div class="total-line final-total"><span>TOTAL:</span><span>${totalAmount.toLocaleString()} EGP</span></div>
                </div>
                <div class="barcode-section">
                    <img src="${barcodeUrl}" class="barcode-image" alt="Barcode">
                    <div style="font-size:10px; margin-top:5px;">${receiptNumber}</div>
                </div>
                <div class="footer">
                    <div>Thank you for shopping with us!</div>
                    <div>IBS Mobile Shop - Your trusted partner</div>
                </div>
                </body></html>`;
        }

        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.nav-tab').forEach(btn => btn.classList.remove('active'));
            const tabEl = document.getElementById(tabName);
            if (tabEl) tabEl.classList.add('active');
            const activeBtn = Array.from(document.querySelectorAll('.nav-tab')).find(btn =>
                btn.getAttribute('onclick') && btn.getAttribute('onclick').includes("'" + tabName + "'")
            );
            if (activeBtn) activeBtn.classList.add('active');
            if (tabName === 'inventory') loadInventory();
        }

        async function loadProducts() {
            try {
                const response = await fetch('api/products.php');
                const result = await response.json();
                if (result.success) {
                    allProducts = result.data;
                    console.log('Products loaded:', allProducts.length);
                }
            } catch (error) {
                console.error('Error loading products:', error);
            }
        }

        // Load inventory (Enterprise)
        async function loadInventory() {
            try {
                const response = await fetch('api/products.php');
                const result = await response.json();
                if (result.success) {
                    allInventoryProducts = result.data;

                    const searchInput = document.getElementById('inventory-search');
                    if (searchInput) searchInput.value = '';

                    displayInventory(result.data);
                    displayInventoryStats(result.data);

                    const resultsCountDiv = document.getElementById('search-results-count');
                    if (resultsCountDiv) {
                        resultsCountDiv.textContent = `Showing all ${result.data.length} products`;
                        resultsCountDiv.style.color = '#666';
                    }
                }
            } catch (error) {
                console.error('Error loading inventory:', error);
            }
        }

        // Display inventory (Enterprise)
        async function displayInventory(products) {
            const tbody = document.getElementById('inventory-tbody');
            if (!tbody) return;

            tbody.innerHTML = products.map(product => {
                const isOutOfStock = product.available_stock === 0;
                const isLowStock = product.min_stock !== null && product.available_stock > 0 && product.available_stock <= product.min_stock;

                let stockStatusHtml = '';
                if (isOutOfStock) {
                    stockStatusHtml = `<span class="inv-badge badge-danger">🚫 Out of Stock (0)</span>`;
                } else if (isLowStock) {
                    stockStatusHtml = `<span class="inv-badge badge-warning">⚠️ Low Stock (${product.available_stock})</span>`;
                } else {
                    stockStatusHtml = `<span class="inv-badge badge-success">✅ In Stock (${product.available_stock})</span>`;
                }

                const serializationBadge = `
                    <div class="inv-badge ${product.serialized_count === product.total_stock ? 'badge-success' : 'badge-warning'}"
                         style="font-size: 11px; margin-top: 5px;">
                        🔢 ${product.serialized_count} / ${product.total_stock} Serialized
                    </div>
                `;

                const productImage = product.image_url ?
                    `<img src="${product.image_url}" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid var(--inv-border); margin-right: 10px;">` :
                    `<div style="width: 40px; height: 40px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 20px; border: 1px solid var(--inv-border); margin-right: 10px;">📦</div>`;

                const colorBadge = `
                    <div class="inv-color-badge" title="Color: ${product.color || 'N/A'}">
                        <span class="color-dot" style="background-color: ${product.color || 'transparent'};"></span>
                        <span>${product.color || 'N/A'}</span>
                    </div>
                `;

                return `
                <tr>
                    <td class="num-col" style="font-weight: 700; color: var(--inv-primary); border-left: 3px solid var(--inv-primary); padding-left: 15px;">
                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 4px;">
                            <span>${product.code}</span>
                            ${serializationBadge}
                        </div>
                    </td>
                    <td style="font-weight: 600; color: #0f172a;">
                        <div style="display: flex; align-items: center;">
                            ${productImage}
                            <span>${product.brand} ${product.model}</span>
                        </div>
                    </td>
                    <td><span class="inv-badge badge-info" style="font-size: 10px;">${product.category_name}</span></td>
                    <td style="font-size: 13px; color: #475569;">${product.brand}</td>
                    <td style="font-size: 13px; color: #475569;">${product.model}</td>
                    <td>${colorBadge}</td>
                    <td style="font-size: 12px; font-weight: 500; color: #64748b;">${product.supplier_name || '<span style="color: #cbd5e1;">N/A</span>'}</td>
                    <td class="num-col" style="color: #64748b; font-size: 12px;">${(product.purchase_price || 0).toLocaleString()}</td>
                    <td class="num-col" style="font-weight: 700; color: #0f172a;">${(product.suggested_price || 0).toLocaleString()} <span style="font-size: 10px; font-weight: 400;">EGP</span></td>
                    <td class="num-col" style="color: #ef4444; font-size: 12px;">${(product.min_selling_price || 0).toLocaleString()}</td>
                    <td class="num-col" style="font-weight: 700; color: #0f172a;">${product.available_stock}</td>
                    <td class="num-col" style="color: #94a3b8; font-size: 12px;">${product.min_stock || 0}</td>
                    <td>
                        <div style="margin-bottom: 4px;">${stockStatusHtml}</div>
                    </td>
                    <td>
                        <div class="inv-actions" style="justify-content: flex-end;">
                            <button class="inv-btn inv-btn-primary" onclick="viewUnits(${product.id})" title="View Items">👁️ View</button>
                            <button class="inv-btn" onclick="printProductLabelEnterprise(${product.id})" title="Print Label">🏷️ Label</button>
                        </div>
                    </td>
                </tr>
                `;
            }).join('');
        }

        // Display inventory stats (Enterprise)
        function displayInventoryStats(products) {
            const totalProducts = products.length;
            const inventoryValue = products.reduce((sum, p) => sum + ((p.purchase_price || 0) * p.available_stock), 0);
            const lowStock = products.filter(p => p.min_stock !== null && p.available_stock > 0 && p.available_stock <= p.min_stock).length;
            const outOfStock = products.filter(p => p.available_stock === 0).length;

            if (document.getElementById('stat-total-products')) {
                document.getElementById('stat-total-products').innerText = totalProducts.toLocaleString();
                document.getElementById('stat-inventory-value').innerText = inventoryValue.toLocaleString() + ' EGP';
                document.getElementById('stat-low-stock').innerText = lowStock.toLocaleString();
                document.getElementById('stat-out-of-stock').innerText = outOfStock.toLocaleString();
            }
        }

        // Enterprise filter (category + brand + search)
        function filterInventoryEnterprise() {
            const searchTerm = document.getElementById('inventory-search').value.toLowerCase();
            const categoryFilter = document.getElementById('filter-category').value;
            const brandFilter = document.getElementById('filter-brand').value;
            const resultsCountDiv = document.getElementById('search-results-count');

            const filtered = allInventoryProducts.filter(product => {
                const matchesSearch = !searchTerm ||
                    product.code.toLowerCase().includes(searchTerm) ||
                    product.brand.toLowerCase().includes(searchTerm) ||
                    product.model.toLowerCase().includes(searchTerm) ||
                    (product.description && product.description.toLowerCase().includes(searchTerm));

                const matchesCategory = !categoryFilter || product.category_name === categoryFilter;
                const matchesBrand = !brandFilter || product.brand === brandFilter;

                return matchesSearch && matchesCategory && matchesBrand;
            });

            displayInventory(filtered);
            displayInventoryStats(filtered);

            if (resultsCountDiv) {
                if (filtered.length === 0) {
                    resultsCountDiv.innerHTML = '<span style="color: var(--inv-danger);">❌ No products found matching your criteria.</span>';
                } else {
                    resultsCountDiv.innerHTML = `✅ Found <b>${filtered.length}</b> products.`;
                }
            }
        }

        // View unit details modal
        async function viewUnits(productId) {
            const modal = document.getElementById('unitDetailsModal');
            const tbody = document.getElementById('unit-details-tbody');
            if (!modal || !tbody) return;

            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px;">⏳ Loading unit details...</td></tr>';
            modal.style.display = 'flex';

            try {
                const response = await fetch(`api/stock_items.php?product_id=${productId}`);
                const res = await response.json();

                if (res.success && res.data.length > 0) {
                    tbody.innerHTML = res.data.map(unit => `
                        <tr>
                            <td class="num-col" style="font-weight: 700;">#${unit.id}</td>
                            <td>
                                <input type="text" id="unit-imei-${unit.id}" class="inv-search-input" style="padding: 6px; font-size: 13px; width: 140px;" value="${unit.imei || ''}" placeholder="Enter IMEI">
                            </td>
                            <td>
                                <input type="text" id="unit-serial-${unit.id}" class="inv-search-input" style="padding: 6px; font-size: 13px; width: 140px;" value="${unit.serial_number || ''}" placeholder="Enter Serial">
                            </td>
                            <td style="text-align: center;">
                                <div style="font-family: monospace; font-size: 10px; margin-bottom: 4px;">${unit.item_code}</div>
                                <svg id="unit-barcode-${unit.id}" style="width: 100px; height: 30px;"></svg>
                            </td>
                            <td><span class="inv-badge badge-success">${unit.status}</span></td>
                            <td style="font-size: 12px; color: var(--inv-text-muted);">${new Date(unit.created_at).toLocaleDateString()}</td>
                            <td style="text-align: center;">
                                <button class="inv-btn inv-btn-primary" onclick="saveUnitDetails(${unit.id})" style="padding: 6px 10px;">💾 Save</button>
                            </td>
                        </tr>
                    `).join('');

                    // Generate barcodes (requires JsBarcode — add it to the head if not already included)
                    if (typeof JsBarcode !== 'undefined') {
                        res.data.forEach(unit => {
                            JsBarcode(`#unit-barcode-${unit.id}`, unit.item_code, {
                                format: "CODE128",
                                width: 1.5,
                                height: 30,
                                displayValue: false
                            });
                        });
                    }
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--inv-text-muted);">No units found for this product.</td></tr>';
                }
            } catch (e) {
                console.error(e);
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--inv-danger);">Failed to load unit details.</td></tr>';
            }
        }

        // Save unit details
        async function saveUnitDetails(unitId) {
            const imei = document.getElementById(`unit-imei-${unitId}`).value.trim();
            const serial = document.getElementById(`unit-serial-${unitId}`).value.trim();

            try {
                const response = await fetch('api/stock_items.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: unitId, imei: imei, serial_number: serial })
                });

                const res = await response.json();
                if (res.success) {
                    alert('Unit details updated successfully!');
                } else {
                    alert('Failed to update: ' + res.message);
                }
            } catch (e) {
                console.error(e);
                alert('An error occurred while saving.');
            }
        }

        // Print product label
        function printProductLabelEnterprise(productId) {
            const product = allInventoryProducts.find(p => p.id === productId);
            if (!product) return;

            const printWindow = window.open('', '_blank', 'width=400,height=600');
            const barcodeValue = product.code || product.id.toString();

            printWindow.document.write(`
                <html>
                <head>
                    <title>Print Label - ${product.brand} ${product.model}</title>
                    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
                    <style>
                        @page { size: auto; margin: 0; }
                        body { font-family: 'Inter', sans-serif; margin: 0; padding: 20px; width: 80mm; text-align: center; }
                        .label-card { border: 1px dashed #ccc; padding: 15px; border-radius: 8px; }
                        .store-name { font-size: 12px; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; color: #64748b; }
                        .product-code { font-size: 14px; color: #3b82f6; font-weight: 700; margin-bottom: 5px; }
                        .product-name { font-size: 18px; font-weight: 800; margin-bottom: 15px; line-height: 1.2; }
                        .price-box { background: #000; color: #fff; display: inline-block; padding: 8px 16px; border-radius: 6px; font-size: 24px; font-weight: 900; margin-bottom: 15px; }
                        .price-box span { font-size: 14px; font-weight: 400; margin-left: 4px; }
                        svg#barcode { width: 100%; max-height: 80px; }
                    </style>
                </head>
                <body>
                    <div class="label-card">
                        <div class="store-name">IBS SMART SOLUTIONS</div>
                        <div class="product-code">${product.code}</div>
                        <div class="product-name">${product.brand} ${product.model}</div>
                        <div><svg id="barcode"></svg></div>
                    </div>
                    <script>
                        JsBarcode("#barcode", "${barcodeValue}", {
                            format: "CODE128", width: 2, height: 60, displayValue: true, fontSize: 14, margin: 10
                        });
                        setTimeout(() => { window.print(); window.close(); }, 500);
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
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
            loadProducts();
            updateReceiptDisplay();
            updatePaymentTotals();
        });
    </script>
    <!-- Modern Success Modal -->
    <div id="successModalOverlay" class="ibs-modal-overlay">
        <div class="ibs-modal-card">
            <div class="ibs-modal-icon">✓</div>
            <h2 class="ibs-modal-title" data-translate="sales.saleCompleted">Sale Completed!</h2>
            <div class="ibs-modal-body">
                <p><span data-translate="sales.receiptGenerated">Receipt # has been generated successfully.</span></p>
                <p><strong>#<span id="successReceiptNumber">---</span></strong></p>
                <div style="margin-top: 15px; padding: 15px; background: var(--gray-50); border-radius: var(--radius-sm); border: 1px dashed var(--gray-200);">
                    <span style="display: block; font-size: 14px; color: var(--gray-500); margin-bottom: 5px;" data-translate="sales.totalAmountPaid">Total Amount Paid</span>
                    <span id="successTotalAmount" style="font-size: 24px; font-weight: 700; color: var(--primary-green);">0.00 EGP</span>
                </div>
            </div>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button class="btn-modern btn-primary-modern" onclick="closeSuccessModal()">
                    <span data-translate="sales.gotIt">Got it</span>
                </button>
            </div>
        </div>
    </div>
</body>

</html>
