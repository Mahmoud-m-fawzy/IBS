<?php
// Staff Receipt Tab Content
?>
<!-- Receipt Tab -->
<div id="receipt" class="tab-content active">
    <div class="section" style="background: transparent; box-shadow: none; padding: 0;">
        <div class="receipt-builder">
            <!-- Left Side: Form Controls -->
            <div class="receipt-left">
                <!-- Customer Information Card -->
                <div class="receipt-card">
                    <h3 data-translate="sales.customerInfo">👤 Customer Information</h3>
                    <div class="modern-input-group">
                        <label data-translate="sales.customerName">Customer Name</label>
                        <input type="text" id="customer-name" class="modern-input" 
                            data-translate-placeholder="sales.customerNamePlaceholder"
                            placeholder="Type customer name (optional)">
                    </div>
                    <div class="modern-input-group">
                        <label data-translate="sales.customerPhone">Customer Phone</label>
                        <input type="tel" id="customer-phone" class="modern-input" 
                            data-translate-placeholder="sales.customerPhonePlaceholder"
                            placeholder="Type phone number (optional)"
                            pattern="[0-9]*"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                </div>

                <!-- Product Selection Card -->
                <div class="receipt-card">
                    <h3 data-translate="sales.searchProducts">🔍 Add Products</h3>
                    
                    <!-- Scan Barcode Utility -->
                    <div style="margin-bottom: 20px;">
                        <button type="button" class="btn-modern btn-primary-modern" style="width: 100%;" onclick="startBarcodeScan()" data-translate="inventory.scanBarcode">
                            📷 Scan Barcode
                        </button>
                    </div>

                    <div class="modern-input-group" style="position: relative;">
                        <label data-translate="sales.chooseProduct">Search Product (Type or Scan)</label>
                        <input type="text" id="product-search" class="modern-input"
                            data-translate-placeholder="sales.searchProducts"
                            placeholder="Search by code, brand, or model..."
                            oninput="handleProductInput(this.value); searchProducts(this.value)"
                            onkeypress="if(event.key==='Enter') selectFirstProduct()">
                        <div style="position: absolute; right: 15px; top: 38px; color: var(--gray-400);">
                            <span id="scan-indicator">📷</span>
                        </div>
                        <div id="scan-feedback" style="margin-top: 5px; font-size: 12px; color: var(--gray-600); display: none;" data-translate="inventory.scanning">
                            Scanning barcode...
                        </div>
                        <div id="receipt-product-search-results" class="product-results-modern" style="display: none;"></div>
                    </div>

                    <div class="modern-input-group">
                        <label data-translate="sales.selectedProduct">Selected Product</label>
                        <div id="selected-product" class="modern-input" style="background: var(--gray-50); height: auto; min-height: 45px; color: var(--gray-600);" data-translate="sales.noProductSelected">
                            No product selected
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="modern-input-group">
                            <label data-translate="sales.sellingPrice">Selling Price (EGP)</label>
                            <input type="text" id="selling-price-display" class="modern-input" placeholder="0.00" oninput="formatPriceInput(this)">
                            <input type="hidden" id="selling-price">
                        </div>
                        <div class="modern-input-group">
                            <label data-translate="sales.quantity">Quantity</label>
                            <input type="number" id="quantity" class="modern-input" min="1" value="1">
                        </div>
                    </div>

                    <button class="btn-modern btn-primary-modern" style="width: 100%; margin-top: 10px;" onclick="showItemSelectionModal()" id="add-product-btn" disabled data-translate="sales.addToReceipt">
                        ➕ Add to Receipt
                    </button>
                </div>
            </div>

            <!-- Item Selection Modal -->
            <div id="item-selection-modal" class="ibs-modal-overlay">
                <div class="modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 0; border-radius: 15px; max-width: 650px; width: 95%; max-height: 85vh; display: flex; flex-direction: column; box-shadow: var(--shadow-xl); overflow: hidden;">
                    <!-- Modal Header -->
                    <div style="background: var(--gradient-primary); padding: 20px 25px; color: white; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; font-size: 1.4em; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 1.2em;">🔍</span> Select Specific Items
                        </h3>
                        <button onclick="closeItemSelectionModal()" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); font-size: 20px; cursor: pointer; color: white; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: var(--transition-fast);" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">×</button>
                    </div>
                    
                    <div style="padding: 25px; overflow-y: auto; flex-grow: 1;">
                        <!-- Product Info Summary -->
                        <div id="selected-product-info" style="margin-bottom: 20px; padding: 15px; background: var(--gray-50); border-radius: 12px; border-left: 5px solid var(--primary-blue); display: flex; justify-content: space-between; align-items: center;">
                        </div>
                        
                        <!-- Control Row -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                            <div class="modern-input-group">
                                <label style="font-weight: 700; color: var(--dark-blue); font-size: 0.9em; margin-bottom: 8px; display: block;">Selling Price (EGP)</label>
                                <input type="text" id="modal-selling-price" class="modern-input" placeholder="0.00" oninput="formatPriceInput(this)" style="max-width: 100%; height: 45px; font-size: 1.1em; font-weight: 700; color: var(--primary-green);">
                            </div>
                            
                            <div class="modern-input-group">
                                <label style="font-weight: 700; color: var(--dark-blue); font-size: 0.9em; margin-bottom: 8px; display: block;">Quantity to Select</label>
                                <input type="number" id="modal-quantity" class="modern-input" min="1" value="1" onchange="updateItemSelection()" style="max-width: 100%; height: 45px; font-weight: 700;">
                            </div>
                        </div>
                        
                        <!-- Items List Section -->
                        <div style="margin-top: 10px;">
                            <h4 style="margin-bottom: 12px; font-size: 1em; color: var(--gray-700); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                                📦 Available Units List
                            </h4>
                            <div id="available-items-list">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal Footer -->
                    <div style="padding: 20px 25px; background: var(--gray-50); border-top: 1px solid var(--gray-200); display: flex; gap: 15px; justify-content: flex-end;">
                        <button class="btn-modern btn-secondary-modern" onclick="closeItemSelectionModal()" style="padding: 12px 25px; border-radius: 10px; font-weight: 600;">Cancel</button>
                        <button class="btn-modern btn-primary-modern" onclick="addSelectedItemsToReceipt()" style="padding: 12px 30px; border-radius: 10px; font-weight: 700; min-width: 220px;" id="modal-add-btn">Add Selected Items</button>
                    </div>
                </div>
            </div>

            <!-- Right Side: Items and Payments -->
            <div class="receipt-right">
                <!-- Receipt Items Card -->
                <div class="receipt-card" style="min-height: 400px; display: flex; flex-direction: column;">
                    <h3 data-translate="sales.receiptItems">🧾 Receipt Items</h3>
                    <div id="receipt-items-container" style="flex-grow: 1; overflow-y: auto; margin-bottom: 20px; border: 1px solid var(--gray-100); border-radius: var(--radius-sm);">
                        <table class="professional-table" id="receipt-items-table">
                            <thead>
                                <tr>
                                    <th data-translate="inventory.product">Product</th>
                                    <th class="text-right" data-translate="inventory.price">Price</th>
                                    <th class="text-right" data-translate="sales.quantity">Qty</th>
                                    <th class="text-right" style="width: 120px;" data-translate="sales.total">Total</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="receipt-items-body">
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--gray-400); padding: 40px;" data-translate="sales.noItemsAdded">
                                        No items added yet
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="receipt-totals" style="padding: 15px; background: var(--gray-50); border-radius: var(--radius-sm);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 1.2em; font-weight: 700; color: var(--gray-700);" data-translate="sales.grandTotal">Grand Total:</span>
                            <span id="total" style="font-size: 1.8em; font-weight: 800; color: var(--primary-blue);">0.00 EGP</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods Card -->
                <div class="receipt-card">
                    <h3>💳 <span data-translate="sales.paymentMethod">Payment Methods</span></h3>
                    
                    <div id="splitPaymentContainer">
                        <div class="payment-row-modern" data-payment-row="0">
                            <select class="modern-input payment-method-select" onchange="updatePaymentTotals()">
                                <option value="" data-translate="sales.method">Method</option>
                                <option value="Cash" data-translate="sales.cash">Cash</option>
                                <option value="Visa" data-translate="sales.card">Visa</option>
                                <option value="Instapay">Instapay</option>
                                <option value="Installment">Installment</option>
                            </select>
                            <input type="number" class="modern-input payment-amount" data-translate-placeholder="sales.amount" placeholder="Amount" step="0.01" min="0" oninput="updatePaymentTotals()">
                            <input type="text" class="modern-input payment-reference" data-translate-placeholder="sales.reference" placeholder="Ref (optional)">
                            <button class="btn-modern btn-danger-modern" onclick="removePaymentRow(0)" style="display: none; padding: 8px 12px;">×</button>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px; border-top: 1px solid var(--gray-100); padding-top: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <button type="button" class="btn-modern btn-primary-modern" style="padding: 8px 15px; font-size: 13px;" onclick="addPaymentRow()" data-translate="sales.addPayment">
                                ➕ Add Payment
                            </button>
                            <div style="text-align: right;">
                                <div style="font-size: 13px; color: var(--gray-600);"><span data-translate="sales.paid">Paid</span>: <span id="totalPaid" style="font-weight: 700;">0.00</span> EGP</div>
                                <div style="font-size: 13px; color: var(--primary-red);"><span data-translate="sales.remaining">Remaining</span>: <span id="remainingAmount" style="font-weight: 700;">0.00</span> EGP</div>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <button class="btn-modern btn-success-modern" onclick="completeReceipt()" id="complete-btn" disabled data-translate="sales.completeSale">
                                ✅ Complete Sale
                            </button>
                            <button class="btn-modern btn-danger-modern" onclick="clearReceipt()" data-translate="sales.clearReceipt">
                                🗑️ Clear Receipt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
