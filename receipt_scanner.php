<?php
// Barcode Scanner Landing Page
// This page handles barcode scans and displays receipt details directly
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IBS Mobile Shop - Receipt Scanner</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .scanner-input {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-align: center;
            margin: 20px 0;
        }
        .scanner-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        .scan-btn {
            background: #28a745;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            cursor: pointer;
            margin: 10px;
            transition: all 0.3s;
        }
        .scan-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .instructions {
            margin: 20px 0;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        .error {
            background: #dc3545;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin: 10px 0;
            display: none;
        }
        .success {
            background: #28a745;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin: 10px 0;
            display: none;
        }
        .loading {
            display: none;
            margin: 20px 0;
        }
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .receipt-details {
            background: white;
            color: #333;
            border-radius: 15px;
            padding: 30px;
            margin: 20px 0;
            text-align: left;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 15px;
        }
        .receipt-info {
            margin-bottom: 20px;
        }
        .receipt-info div {
            margin-bottom: 8px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th, .items-table td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .items-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .total-section {
            border-top: 2px solid #333;
            padding-top: 15px;
            margin-top: 20px;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 16px;
        }
        .final-total {
            font-weight: bold;
            font-size: 18px;
            color: #28a745;
        }
        .back-btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .back-btn:hover {
            background: #0056b3;
        }
        .print-btn {
            background: #28a745;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin: 10px;
        }
        .print-btn:hover {
            background: #218838;
        }
        .action-buttons {
            text-align: center;
            margin-top: 20px;
        }
        @media print {
            body { background: white; }
            .container { background: white; box-shadow: none; }
            .back-btn, .print-btn, .scan-btn { display: none; }
            .instructions, .error, .success, .loading { display: none; }
        }
    </style>
</head>
<body>
    <div class="container" id="scannerContainer">
        <div class="logo">📱 IBS Mobile Shop</div>
        <h1>🧾 Receipt Scanner</h1>
        <p>Scan or enter receipt ID to view details</p>
        
        <div class="instructions">
            <h3>📱 How to use:</h3>
            <p>1. Scan the barcode on your receipt using a barcode scanner app</p>
            <p>2. The scanner will automatically input the receipt ID</p>
            <p>3. Click "View Receipt" to see full details</p>
            <p>4. Or manually type the receipt ID if needed</p>
        </div>
        
        <input type="text" 
               id="receiptId" 
               class="scanner-input" 
               placeholder="📷 Scan barcode or enter receipt ID..." 
               autofocus>
        
        <button class="scan-btn" onclick="viewReceipt()">🧾 View Receipt</button>
        <button class="scan-btn" onclick="clearInput()" style="background: #6c757d;">🔄 Clear</button>
        
        <div id="error" class="error"></div>
        <div id="success" class="success"></div>
        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p>Loading receipt details...</p>
        </div>
        
        <div style="margin-top: 30px; font-size: 14px; opacity: 0.8;">
            <p>💡 Tip: Most barcode scanner apps will automatically input the ID when you scan</p>
            <p>🔗 Having trouble? Contact our support team</p>
        </div>
    </div>

    <script>
        // Auto-focus on input
        document.getElementById('receiptId').focus();
        
        // Handle Enter key
        document.getElementById('receiptId').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                viewReceipt();
            }
        });
        
        // Handle barcode scanner input (usually ends with Enter)
        let barcodeBuffer = '';
        let barcodeTimeout;
        
        document.getElementById('receiptId').addEventListener('input', function(e) {
            const input = e.target.value;
            
            // Clear previous timeout
            clearTimeout(barcodeTimeout);
            
            // Set new timeout to detect when barcode scanning is complete
            barcodeTimeout = setTimeout(() => {
                if (input.length > 0) {
                    // Assume barcode scanning is complete
                    viewReceipt();
                }
            }, 100);
        });
        
        async function viewReceipt() {
            const receiptId = document.getElementById('receiptId').value.trim();
            
            if (!receiptId) {
                showError('Please enter or scan a receipt ID');
                return;
            }
            
            // Validate that it's a number
            if (!/^\d+$/.test(receiptId)) {
                showError('Invalid receipt ID. Please scan the barcode again.');
                return;
            }
            
            hideMessages();
            showLoading();
            
            try {
                // Fetch receipt details from API
                const response = await fetch(`api/sales.php?id=${receiptId}`);
                const result = await response.json();
                
                if (result.success) {
                    displayReceiptDetails(result.data);
                    showSuccess('Receipt loaded successfully!');
                } else {
                    showError('Receipt not found: ' + result.message);
                }
            } catch (error) {
                console.error('Error fetching receipt:', error);
                showError('Error loading receipt. Please try again.');
            }
        }
        
        function displayReceiptDetails(receipt) {
            const receiptHtml = `
                <div class="receipt-details">
                    <div class="receipt-header">
                        <h2>📱 IBS MOBILE SHOP</h2>
                        <h3>🧾 RECEIPT DETAILS</h3>
                    </div>
                    
                    <div class="receipt-info">
                        <div><strong>Receipt Number:</strong> ${receipt.receipt_number}</div>
                        <div><strong>Date:</strong> ${new Date(receipt.sale_date).toLocaleString()}</div>
                        <div><strong>Staff:</strong> ${receipt.staff_name}</div>
                        <div><strong>Customer:</strong> ${receipt.customer_name || 'Walk-in Customer'}</div>
                        <div><strong>Payment Method:</strong> ${receipt.payment_method || 'Cash'}</div>
                    </div>
                    
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${receipt.items.map(item => `
                                <tr>
                                    <td>
                                        <strong>${item.product_brand} ${item.product_model}</strong><br>
                                        <small style="color: #666;">${item.product_code}</small>
                                    </td>
                                    <td style="text-align: center;">${item.quantity}</td>
                                    <td style="text-align: right;">${item.unit_price.toFixed(2)} EGP</td>
                                    <td style="text-align: right; font-weight: bold;">${item.total_price.toFixed(2)} EGP</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    
                    <div class="total-section">
                        <div class="total-line final-total">
                            <span>💰 TOTAL AMOUNT:</span>
                            <span>${receipt.total_amount.toFixed(2)} EGP</span>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button class="print-btn" onclick="window.print()">🖨️ Print Receipt</button>
                        <button class="back-btn" onclick="resetScanner()">🔄 Scan Another</button>
                    </div>
                </div>
            `;
            
            document.getElementById('scannerContainer').innerHTML = receiptHtml;
        }
        
        function resetScanner() {
            location.reload();
        }
        
        function clearInput() {
            document.getElementById('receiptId').value = '';
            document.getElementById('receiptId').focus();
            hideMessages();
        }
        
        function showError(message) {
            hideMessages();
            document.getElementById('error').textContent = message;
            document.getElementById('error').style.display = 'block';
        }
        
        function showSuccess(message) {
            hideMessages();
            document.getElementById('success').textContent = message;
            document.getElementById('success').style.display = 'block';
        }
        
        function showLoading() {
            hideMessages();
            document.getElementById('loading').style.display = 'block';
        }
        
        function hideMessages() {
            document.getElementById('error').style.display = 'none';
            document.getElementById('success').style.display = 'none';
            document.getElementById('loading').style.display = 'none';
        }
        
        // Auto-detect if we have a hash with receipt ID
        if (window.location.hash && window.location.hash.startsWith('#receipt-details-')) {
            const receiptId = window.location.hash.replace('#receipt-details-', '');
            document.getElementById('receiptId').value = receiptId;
            viewReceipt();
        }
    </script>
</body>
</html>
