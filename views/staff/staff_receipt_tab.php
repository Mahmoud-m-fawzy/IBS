<?php
// Staff Receipt Tab Content
?>
<!-- Receipt Tab -->
<div id="receipt" class="tab-content active">
    <div class="section">
        <h2>🧾 Create Receipt</h2>
        <div class="receipt-builder">
            <div>
                <h3>Customer Information</h3>
                <div class="form-group">
                    <label>Customer Name:</label>
                    <input type="text" id="customer-name" placeholder="Enter customer name" pattern="[A-Za-z\s]+" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')">
                </div>
                <div class="form-group">
                    <label>Customer Phone:</label>
                    <input type="tel" id="customer-phone" placeholder="Enter phone number" pattern="[0-9]*" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>

                <h3>Add Products</h3>
                <div class="form-group">
                    <label>Search Product (Type or Scan Barcode):</label>
                    <div style="position: relative;">
                        <input type="text" id="product-search"
                            placeholder="🔍 Type product code, brand, model, or scan barcode..."
                            style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; padding-right: 40px;"
                            oninput="handleProductInput(this.value); searchProducts(this.value)"
                            onkeypress="if(event.key==='Enter') selectFirstProduct()">
                        <div style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #666;">
                            <span id="scan-indicator"></span>
                        </div>
                    </div>
                    <div id="product-search-results"
                        style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-top: none; display: none; background: white; position: relative; z-index: 100;">
                    </div>
                    <div id="scan-feedback" style="margin-top: 5px; font-size: 12px; color: #666; display: none;">
                        Scanning barcode...
                    </div>
                    <button type="button" onclick="testSearch()" style="margin-top: 5px; padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer;">Test Search</button>
                </div>
                <div class="form-group">
                    <label>Selected Product:</label>
                    <div id="selected-product"
                        style="padding: 10px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; min-height: 40px; color: #666;">
                        No product selected
                    </div>
                </div>
                <div class="form-group">
                    <label>Selling Price: <span style="color: red;">*</span></label>
                    <input type="number" id="selling-price" step="0.01" min="0.01"
                        placeholder="Enter selling price"
                        style="width: 70%; max-width: 70%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                    <small style="color: #666; font-size: 12px;">Must be at least minimum selling
                        price</small>
                </div>
                <div class="form-group">
                    <label>Quantity:</label>
                    <input type="number" id="quantity" min="1" value="1">
                </div>
                <button class="btn" onclick="addToReceipt()" id="add-product-btn" disabled>Add to
                    Receipt</button>
            </div>

            <div>
                <h3>Receipt Items</h3>
                <div class="receipt-items" id="receipt-items">
                    <div class="no-data">No items added yet</div>
                </div>

                <div class="receipt-totals">
                    <div class="total-final">Total: <span id="total">0.00 EGP</span></div>
                </div>

                <div class="payment-method-section" style="margin: 15px 0;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Payment Methods:</label>
                    
                    <div id="splitPaymentContainer">
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
                    </div>
                    
                    <div style="margin-top: 10px;">
                        <button type="button" class="btn btn-sm btn-primary" onclick="addPaymentRow()">+ Add Payment Method</button>
                        <div style="margin-top: 5px; font-size: 12px; color: #666;">
                            Total Paid: <span id="totalPaid" style="font-weight: bold;">0.00</span> EGP | 
                            Remaining: <span id="remainingAmount" style="font-weight: bold; color: red;">0.00</span> EGP
                        </div>
                    </div>
                </div>

                <button class="btn btn-success" onclick="completeReceipt()" id="complete-btn" disabled>Complete
                    Sale</button>
                <button class="btn btn-danger" onclick="clearReceipt()">Clear Receipt</button>
            </div>
        </div>
    </div>
</div>
