<?php
// Staff Inventory Tab Content
?>
<style>
    :root {
        --inv-primary: #3b82f6;
        --inv-success: #10b981;
        --inv-warning: #f59e0b;
        --inv-danger: #ef4444;
        --inv-gray: #64748b;
        --inv-bg: #f8fafc;
        --inv-card-bg: #ffffff;
        --inv-border: #e2e8f0;
        --inv-text: #1e293b;
        --inv-text-muted: #64748b;
        --inv-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    #inventory {
        background: var(--inv-bg);
        padding: 24px;
        min-height: 100vh;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .inv-container {
        max-width: 1600px;
        margin: 0 auto;
    }

    /* Summary Cards */
    .inv-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 24px;
        margin-bottom: 32px;
    }

    .inv-stat-card {
        background: var(--inv-card-bg);
        border-radius: 16px;
        padding: 24px;
        box-shadow: var(--inv-shadow);
        border: 1px solid var(--inv-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: transform 0.2s, box-shadow 0.2s;
        height: 120px;
    }

    .inv-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .inv-stat-info h3 {
        font-size: 28px;
        font-weight: 800;
        color: var(--inv-text);
        margin: 0;
        line-height: 1;
    }

    .inv-stat-info p {
        font-size: 14px;
        color: var(--inv-text-muted);
        margin: 4px 0 0 0;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .inv-stat-icon {
        font-size: 32px;
        background: #eff6ff;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        color: var(--inv-primary);
    }

    /* Cards Theme */
    .card-total .inv-stat-icon { color: #3b82f6; background: #eff6ff; }
    .card-value .inv-stat-icon { color: #8b5cf6; background: #f5f3ff; }
    .card-low .inv-stat-icon { color: #f59e0b; background: #fffbeb; }
    .card-out .inv-stat-icon { color: #ef4444; background: #fef2f2; }

    /* Search & Filter Bar */
    .inv-search-section {
        background: var(--inv-card-bg);
        border-radius: 16px;
        padding: 20px;
        box-shadow: var(--inv-shadow);
        border: 1px solid var(--inv-border);
        margin-bottom: 24px;
        display: flex;
        gap: 16px;
        align-items: center;
    }

    .inv-search-wrapper {
        position: relative;
        flex: 1;
    }

    .inv-search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--inv-text-muted);
        pointer-events: none;
    }

    .inv-search-input {
        width: 100%;
        padding: 12px 16px 12px 48px;
        border-radius: 12px;
        border: 1px solid var(--inv-border);
        background: #f1f5f9;
        font-size: 15px;
        transition: all 0.2s;
        outline: none;
    }

    .inv-search-input:focus {
        background: #fff;
        border-color: var(--inv-primary);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .inv-filter-dropdown {
        padding: 12px 16px;
        border-radius: 12px;
        border: 1px solid var(--inv-border);
        background: #fff;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        outline: none;
        min-width: 160px;
        transition: var(--inv-shadow);
    }

    .inv-filter-dropdown:focus {
        border-color: var(--inv-primary);
    }

    /* Professional Table */
    .inv-table-container {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        border: 1px solid var(--inv-border);
        overflow-x: auto;
        margin-top: 20px;
    }

    .inv-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .inv-table thead {
        background: #f8fafc;
    }

    .inv-table th {
        padding: 16px 12px;
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
        border-bottom: 1px solid var(--inv-border);
        text-align: left;
    }

    .inv-table td {
        padding: 14px 12px;
        font-size: 13.5px;
        color: #1e293b;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .inv-table tr:hover td {
        background: #fdfdfd;
    }

    .inv-table tr:last-child td {
        border-bottom: none;
    }

    .num-col { 
        text-align: right; 
        font-family: 'JetBrains Mono', 'Fira Code', monospace; 
        font-weight: 600;
    }

    /* Badges */
    .inv-badge {
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }

    /* Color Badge Styling */
    .inv-color-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
    }

    .color-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        box-shadow: inset 0 0 0 1px rgba(0,0,0,0.1);
    }

    .badge-success { background: #dcfce7; color: #166534; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-info { background: #dbeafe; color: #1e40af; }
    .badge-gray { background: #f1f5f9; color: #475569; }

    /* Actions */
    .inv-actions {
        display: flex;
        gap: 8px;
    }

    .inv-btn {
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid var(--inv-border);
        background: #fff;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .inv-btn:hover { background: #f8fafc; border-color: var(--inv-primary); color: var(--inv-primary); }
    .inv-btn-primary { background: var(--inv-primary); color: #fff; border: none; }
    .inv-btn-primary:hover { background: #2563eb; color: #fff; }

    /* Animations */
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .section-animate { animation: fadeIn 0.4s ease-out backwards; }
</style>

<div id="inventory" class="tab-content">
    <div class="inv-container">
        <!-- Summary Cards -->
        <div id="inventory-stats" class="inv-stats-grid">
            <div class="inv-stat-card card-total section-animate" style="animation-delay: 0.1s;">
                <div class="inv-stat-info">
                    <h3 id="stat-total-products">-</h3>
                    <p data-translate="reports.totalProducts">Total Products</p>
                </div>
                <div class="inv-stat-icon">📦</div>
            </div>
            <div class="inv-stat-card card-value section-animate" style="animation-delay: 0.2s;">
                <div class="inv-stat-info">
                    <h3 id="stat-inventory-value">-</h3>
                    <p data-translate="inventory.inventoryValue">Inventory Value</p>
                </div>
                <div class="inv-stat-icon">💰</div>
            </div>
            <div class="inv-stat-card card-low section-animate" style="animation-delay: 0.3s;">
                <div class="inv-stat-info">
                    <h3 id="stat-low-stock">-</h3>
                    <p data-translate="inventory.lowStock">Low Stock</p>
                </div>
                <div class="inv-stat-icon">⚠️</div>
            </div>
            <div class="inv-stat-card card-out section-animate" style="animation-delay: 0.4s;">
                <div class="inv-stat-info">
                    <h3 id="stat-out-of-stock">-</h3>
                    <p data-translate="inventory.outOfStock">Out of Stock</p>
                </div>
                <div class="inv-stat-icon">🚫</div>
            </div>
        </div>

        <!-- Search & Filter Section -->
        <div class="inv-search-section section-animate" style="animation-delay: 0.5s;">
            <div class="inv-search-wrapper">
                <span class="inv-search-icon">🔍</span>
                <input type="text" id="inventory-search" class="inv-search-input" 
                       placeholder="Search by code, brand, model, or description..."
                       data-translate-placeholder="sales.searchProducts"
                       onkeyup="filterInventoryEnterprise(this.value)">
            </div>
            <select id="filter-category" class="inv-filter-dropdown" onchange="filterInventoryEnterprise()">
                <option value="" data-translate="inventory.selectCategory">All Categories</option>
            </select>
            <select id="filter-brand" class="inv-filter-dropdown" onchange="filterInventoryEnterprise()">
                <option value="" data-translate="inventory.selectBrand">All Brands</option>
            </select>
        </div>

        <!-- Table Section -->
        <div class="inv-table-container section-animate" style="animation-delay: 0.6s;">
            <div id="search-results-count" style="padding: 12px 20px; font-size: 13px; color: var(--inv-text-muted); border-bottom: 1px solid var(--inv-border); background: #fdfdfe;"></div>
            <table class="inv-table">
                <thead>
                    <tr>
                        <th style="width: 140px;" data-translate="inventory.code">Product Code</th>
                        <th data-translate="inventory.product">Product Name</th>
                        <th data-translate="inventory.category">Category</th>
                        <th data-translate="inventory.brand">Brand</th>
                        <th data-translate="inventory.model">Model</th>
                        <th data-translate="inventory.color">Color</th>
                        <th data-translate="inventory.supplier">Supplier</th>
                        <th class="num-col" data-translate="inventory.purchasePrice">Purchase</th>
                        <th class="num-col" data-translate="inventory.suggestedPrice">Suggested</th>
                        <th class="num-col" data-translate="inventory.minPrice">Min. Selling</th>
                        <th class="num-col" data-translate="inventory.stock">Quantity</th>
                        <th class="num-col" data-translate="inventory.minStock">Min. Stock</th>
                        <th data-translate="inventory.stockDetails">Stock Details</th>
                        <th style="text-align: center;" data-translate="inventory.actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="inventory-tbody">
                    <!-- Data loaded via JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Unit Details Modal (New) -->
<div id="unitDetailsModal" class="ibs-modal-overlay" style="display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
    <div class="ibs-modal-card" style="max-width: 900px; width: 95%; margin: 50px auto; background: white; border-radius: 16px; padding: 0; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="background: var(--inv-bg); padding: 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--inv-border);">
            <h2 style="margin: 0; font-size: 20px; color: var(--inv-text);">📦 Product Unit Details</h2>
            <button onclick="closeUnitDetails()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--inv-text-muted);">&times;</button>
        </div>
        <div style="padding: 24px; max-height: 70vh; overflow-y: auto;">
            <table class="inv-table" style="box-shadow: none; border: 1px solid var(--inv-border);">
                <thead>
                    <tr>
                        <th data-translate="inventory.id">Unit ID</th>
                        <th data-translate="inventory.imei">IMEI</th>
                        <th data-translate="inventory.serial">Serial Number</th>
                        <th data-translate="inventory.barcode">Barcode</th>
                        <th data-translate="inventory.status">Status</th>
                        <th data-translate="inventory.date">Date Added</th>
                        <th style="text-align: center;" data-translate="inventory.actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="unit-details-tbody"></tbody>
            </table>
        </div>
        <div style="padding: 16px 24px; background: #f8fafc; text-align: right; border-top: 1px solid var(--inv-border);">
            <button onclick="closeUnitDetails()" class="inv-btn" style="padding: 10px 20px;">Close Window</button>
        </div>
    </div>
</div>

<script>
// Local initialization for filters
document.addEventListener('DOMContentLoaded', () => {
    loadFilterOptions();
});

function loadFilterOptions() {
    // Reuse existing APIs to fill filters
    fetch('api/categories.php').then(r => r.json()).then(res => {
        if (res.success) {
            const select = document.getElementById('filter-category');
            res.data.forEach(c => select.add(new Option(c.name, c.name)));
        }
    });
    fetch('api/brands.php').then(r => r.json()).then(res => {
        if (res.success) {
            const select = document.getElementById('filter-brand');
            res.data.forEach(b => select.add(new Option(b.name, b.name)));
        }
    });
}

function closeUnitDetails() {
    document.getElementById('unitDetailsModal').style.display = 'none';
}
</script>
