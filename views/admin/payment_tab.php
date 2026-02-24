<?php
// Payment Tab Content
?>
<!-- Payment Tab -->
<div id="payment" class="tab-content">
    <div class="section">
        <h2 data-translate="payment.title">💸 Payment Management</h2>

        <!-- Add Payment Entry Form -->
        <div style="margin-bottom: 30px;">
            <h3 data-translate="payment.addNewPayment">Add New Payment Entry</h3>
            <form id="addPaymentForm">
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                    <div class="form-group">
                        <label data-translate="payment.amount">Amount: <span style="color: red;">*</span></label>
                        <input type="number" id="payment-price" step="0.01" min="0.01" required
                            placeholder="Enter payment amount"
                            style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px;">
                        <small style="color: #666; font-size: 12px;" data-translate="payment.amountGreaterThanZero">Amount must be greater than 0</small>
                    </div>
                    <div class="form-group">
                        <label data-translate="payment.description">Description: <span style="color: red;">*</span></label>
                        <textarea id="payment-description" rows="2" required
                            placeholder="Describe the payment/expense..."
                            style="width: 100%; max-width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 16px; resize: vertical;"></textarea>
                        <small style="color: #666; font-size: 12px;" data-translate="payment.providePaymentDetails">Provide details about this payment entry</small>
                    </div>
                </div>
                <button type="submit" class="btn" style="margin-top: 10px;" data-translate="payment.addPaymentEntry">Add Payment Entry</button>
            </form>
        </div>

        <!-- Payment Entries List -->
        <div>
            <h3 data-translate="payment.paymentEntries">Payment Entries</h3>
            <div id="payment-stats" class="stats-grid" style="margin-bottom: 20px;"></div>
            <table id="payment-table">
                <thead>
                    <tr>
                        <th data-translate="payment.date">Date</th>
                        <th data-translate="payment.name">Name</th>
                        <th data-translate="payment.description">Description</th>
                        <th data-translate="payment.amount">Amount</th>
                        <th data-translate="payment.actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="payment-tbody"></tbody>
            </table>
        </div>
    </div>
</div>
