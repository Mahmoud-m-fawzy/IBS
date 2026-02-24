<?php
// Sales Tab Content: Refined with Professional Color-Coding
?>
<div id="sales" class="tab-content" style="background: #f8fafc; padding: 25px; border-radius: 12px;">
    <!-- Section Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="font-size: 26px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 12px;">
            <span style="background: #e2e8f0; padding: 10px; border-radius: 10px;">📊</span> 
            <span data-translate="sales.title">Sales Management</span>
        </h2>
    </div>

    <!-- PART 1: Colorful Professional Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <!-- Card 1: Sales Today (Indigo) -->
        <div class="prof-card color-indigo">
            <div class="prof-card-info">
                <span class="prof-card-title" data-translate="sales.salesToday">Sales Today</span>
                <span id="stat-sales-today" class="prof-card-value">0</span>
            </div>
            <div class="prof-card-icon">📈</div>
        </div>
        <!-- Card 2: Revenue Today (Emerald) -->
        <div class="prof-card color-emerald">
            <div class="prof-card-info">
                <span class="prof-card-title" data-translate="sales.revenueToday">Revenue Today</span>
                <span id="stat-revenue-today" class="prof-card-value">0 <small style="font-size: 14px;">EGP</small></span>
            </div>
            <div class="prof-card-icon">💵</div>
        </div>
        <!-- Card 3: Monthly Breakdown (Colorful Integrated Card) -->
        <div class="prof-card color-indigo-dark" style="grid-column: span 2; display: block; height: auto;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                <div>
                    <span class="prof-card-title" data-translate="sales.revenueByMethod">Revenue by Payment Method (This Month)</span>
                    <span id="stat-revenue-month-total" class="prof-card-value" style="font-size: 28px; display: block; margin-top: 5px;">0 <small style="font-size: 16px;">EGP</small></span>
                </div>
                <div class="prof-card-icon">💳</div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                <div class="payment-stat-box-colorful">
                    <span class="payment-label" data-translate="sales.cash">Cash</span>
                    <span id="stat-pay-cash" class="payment-value">0</span>
                </div>
                <div class="payment-stat-box-colorful">
                    <span class="payment-label" data-translate="sales.card">Visa</span>
                    <span id="stat-pay-visa" class="payment-value">0</span>
                </div>
                <div class="payment-stat-box-colorful">
                    <span class="payment-label">Instapay</span>
                    <span id="stat-pay-instapay" class="payment-value">0</span>
                </div>
                <div class="payment-stat-box-colorful">
                    <span class="payment-label">Installment</span>
                    <span id="stat-pay-installment" class="payment-value">0</span>
                </div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
         <!-- Extra info cards -->
         <div class="prof-card mini color-indigo-soft">
            <span class="prof-card-title" data-translate="sales.salesMonth">Sales Month</span>
            <span id="stat-sales-month" class="prof-card-value">0</span>
        </div>
        <div class="prof-card mini color-emerald-soft">
            <span class="prof-card-title" data-translate="sales.revenueMonth">Revenue Month</span>
            <span id="stat-revenue-month-mini" class="prof-card-value">0 <small style="font-size: 14px;">EGP</small></span>
        </div>
        <div class="prof-card mini color-amber">
            <span class="prof-card-title" data-translate="sales.soldUnits">Sold Units</span>
            <span id="stat-total-items" class="prof-card-value">0</span>
        </div>
        <div class="prof-card mini color-rose">
            <span class="prof-card-title" data-translate="sales.returnedItems">Returned Items</span>
            <span id="stat-returns" class="prof-card-value">0</span>
        </div>
    </div>

    <!-- PART 2: Advanced Search Panel -->
    <div class="prof-panel" style="margin-bottom: 30px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 8px;">
                🔍 <span data-translate="sales.searchFilters">Advanced Search & Filters</span>
            </h3>
            <button onclick="clearAdvancedSearch()" class="prof-text-btn">
                ♻️ <span data-translate="sales.resetFilters">Reset Filters</span>
            </button>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
            <div class="prof-input-group">
                <label data-translate="inventory.barcode">Barcode</label>
                <input type="text" id="search-barcode" data-translate-placeholder="sales.barcodePlaceholder" placeholder="Scan or type barcode...">
            </div>
            <div class="prof-input-group">
                <label data-translate="sales.customerPhone">Customer Phone</label>
                <input type="text" id="search-phone" data-translate-placeholder="sales.phonePlaceholder" placeholder="Search phone number...">
            </div>
            <div class="prof-input-group">
                <label data-translate="reports.fromDate">Date From</label>
                <input type="date" id="search-date-from">
            </div>
            <div class="prof-input-group">
                <label data-translate="reports.toDate">Date To</label>
                <input type="date" id="search-date-to">
            </div>
            <div class="prof-input-group">
                <label data-translate="sales.id">Sale ID / Receipt</label>
                <input type="text" id="search-general" data-translate-placeholder="sales.idPlaceholder" placeholder="ID or Receipt #">
            </div>
        </div>
        
        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <button onclick="performAdvancedSearch()" class="prof-btn-primary">
                <span data-translate="sales.searchResults">Search Results</span> ⚡
            </button>
        </div>
    </div>

    <!-- PART 3: Professional Table -->
    <div class="prof-panel" style="padding: 0; overflow: hidden;">
        <div style="padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <span id="sales-search-results-count" style="font-size: 14px; color: #64748b; font-weight: 600;">
                Showing all 0 recent sales
            </span>
            <div style="display: flex; gap: 10px;">
                <button onclick="loadSales()" class="prof-icon-btn" title="Refresh">🔄</button>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                <thead>
                    <tr style="background: #f1f5f9;">
                        <th style="width: 65px; text-align: center;" data-translate="inventory.id">ID</th>
                        <th style="width: 160px; text-align: left;" data-translate="sales.invoiceReceipt">Invoice / Receipt</th>
                        <th style="width: 220px; text-align: left;" data-translate="sales.customerDetails">Customer Details</th>
                        <th style="width: 150px; text-align: left;" data-translate="sales.dateTime">Date & Time</th>
                        <th style="width: 90px; text-align: center;" data-translate="sales.netItems">Net Items</th>
                        <th style="width: 140px; text-align: right;" data-translate="sales.totalAmount">Total Amount</th>
                        <th style="width: 120px; text-align: center;" data-translate="sales.status">Status</th>
                        <th style="width: 130px; text-align: center;" data-translate="inventory.actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="sales-tbody">
                    <!-- Content populated by JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Base Professional Card */
.prof-card {
    background: white;
    padding: 22px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}
.prof-card.mini {
    padding: 18px;
    justify-content: center;
    flex-direction: column;
    text-align: center;
}
.prof-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.06);
}

/* Colorful Card Variants */
.color-indigo { background: linear-gradient(135deg, #f5f3ff 0%, #ffffff 100%); border-left: 5px solid #6366f1; }
.color-indigo .prof-card-icon { color: #6366f1; }
.color-indigo-soft { background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%); border-left: 5px solid #3b82f6; }
.color-indigo-soft .prof-card-icon { color: #3b82f6; }
.color-indigo-dark { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); border-left: 5px solid #4f46e5; }
.color-indigo-dark .prof-card-title { color: #94a3b8; }
.color-indigo-dark .prof-card-value { color: #f8fafc; }
.color-indigo-dark .prof-card-icon { color: #6366f1; }

.color-emerald { background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%); border-left: 5px solid #10b981; }
.color-emerald .prof-card-icon { color: #10b981; }
.color-emerald-soft { background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border-left: 5px solid #22c55e; }
.color-emerald-soft .prof-card-icon { color: #22c55e; }

.color-amber { background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%); border-left: 5px solid #f59e0b; }
.color-amber .prof-card-icon { color: #f59e0b; }
.color-amber .prof-card-value { color: #92400e; }

.color-rose { background: linear-gradient(135deg, #fff1f2 0%, #ffffff 100%); border-left: 5px solid #f43f5e; }
.color-rose .prof-card-icon { color: #f43f5e; }
.color-rose .prof-card-value { color: #be123c; }

/* Text & Value Styles */
.prof-card-title {
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 6px;
    display: block;
}
.prof-card-value {
    font-size: 26px;
    font-weight: 800;
    color: #1e293b;
    display: block;
}
.prof-card-icon {
    font-size: 32px;
    opacity: 0.9;
}

/* Payment Box Styles */
.payment-stat-box-colorful {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(4px);
    padding: 12px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
    transition: all 0.2s;
}
.color-indigo-dark .payment-stat-box-colorful {
    border-color: #475569;
}
.color-indigo-dark .payment-label { color: #94a3b8; }
.color-indigo-dark .payment-value { color: #ffffff; }

.payment-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 4px;
}
.payment-value {
    font-size: 15px;
    font-weight: 800;
    color: #334155;
}

/* Other UI Elements */
.prof-panel {
    background: white;
    border-radius: 12px;
    padding: 24px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.prof-input-group label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #475569;
    margin-bottom: 8px;
}
.prof-input-group input {
    width: 100%;
    padding: 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}
.prof-input-group input:focus {
    border-color: #6366f1;
}

.prof-btn-primary {
    background: #1e293b;
    color: white;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 15px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}
.prof-btn-primary:hover {
    background: #0f172a;
    transform: translateY(-1px);
}

.prof-text-btn { background: none; border: none; color: #6366f1; font-weight: 700; font-size: 14px; cursor: pointer; }
.prof-icon-btn { background: white; border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; cursor: pointer; transition: background 0.2s; }
.prof-icon-btn:hover { background: #f8fafc; }

/* Table Enhancements */
table th { padding: 16px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; background: #f8fafc; }
table td { padding: 16px; font-size: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; vertical-align: middle; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
table tbody tr:hover { background: #f9fafb; }

.status-badge-prof { padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
.status-completed { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.status-returned { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.status-partial { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

.action-btn-prof { 
    width: 36px; height: 36px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; 
    display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; 
}
.action-btn-prof:hover { border-color: #6366f1; color: #6366f1; background: #f5f3ff; }

/* RTL Adjustments */
body.rtl .prof-card { border-left: none; border-right: 5px solid; }
body.rtl .color-indigo { border-right-color: #6366f1; }
body.rtl .color-emerald { border-right-color: #10b981; }
body.rtl .color-amber { border-right-color: #f59e0b; }
body.rtl .color-rose { border-right-color: #f43f5e; }
body.rtl .color-indigo-soft { border-right-color: #3b82f6; }
body.rtl .color-emerald-soft { border-right-color: #22c55e; }
body.rtl .color-indigo-dark { border-right-color: #4f46e5; }
</style>
