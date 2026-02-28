<?php
// Customers Tab Content
?>

<!-- Customers Management Tab -->
<div id="customers" class="tab-content" style="background: #f8fafc; padding: 25px; border-radius: 12px;">

    <!-- Section Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="font-size: 26px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 12px;">
            <span style="background: #e2e8f0; padding: 10px; border-radius: 10px;">👥</span>
            <span data-translate="customers.title">Customer Management</span>
        </h2>
        <div style="display: flex; gap: 10px;">
            <button onclick="openBulkCustomerSmsModal()" class="cust-btn-primary" style="background: #6366f1;">
                💬 <span data-translate="customers.sendMessage">Send Message to All</span>
            </button>
            <button onclick="toggleAddCustomerPanel()" class="cust-btn-primary" id="addCustomerToggleBtn">
                ➕ <span data-translate="customers.addCustomer">Add Customer</span>
            </button>
        </div>
    </div>

    <!-- PART 1: Stat Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <!-- Total Customers -->
        <div class="prof-card color-indigo">
            <div class="prof-card-info">
                <span class="prof-card-title" data-translate="customers.totalCustomers">Total Customers</span>
                <span id="cust-stat-total" class="prof-card-value">0</span>
            </div>
            <div class="prof-card-icon">👥</div>
        </div>
        <!-- Total Revenue -->
        <div class="prof-card color-emerald">
            <div class="prof-card-info">
                <span class="prof-card-title" data-translate="customers.totalRevenue">Total Revenue</span>
                <span id="cust-stat-revenue" class="prof-card-value">0 <small style="font-size:14px">EGP</small></span>
            </div>
            <div class="prof-card-icon">💰</div>
        </div>
        <!-- New This Month -->
        <div class="prof-card color-amber">
            <div class="prof-card-info">
                <span class="prof-card-title" data-translate="customers.newThisMonth">New This Month</span>
                <span id="cust-stat-new" class="prof-card-value">0</span>
            </div>
            <div class="prof-card-icon">🆕</div>
        </div>
        <!-- Top Customer -->
        <div class="prof-card color-rose">
            <div class="prof-card-info">
                <span class="prof-card-title" data-translate="customers.topCustomer">Top Customer</span>
                <span id="cust-stat-top" class="prof-card-value" style="font-size: 16px; font-weight: 700;">—</span>
            </div>
            <div class="prof-card-icon">🏆</div>
        </div>
    </div>

    <!-- PART 2: Add Customer Panel (collapsible) -->
    <div id="addCustomerPanel" class="prof-panel" style="margin-bottom: 30px; display: none;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 8px;">
                ➕ <span data-translate="customers.addCustomer">Add New Customer</span>
            </h3>
            <button onclick="toggleAddCustomerPanel()" class="prof-text-btn">✕ Close</button>
        </div>
        <form id="addCustomerForm" onsubmit="addCustomer(event)">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div class="prof-input-group">
                    <label data-translate="customers.name">Name <span style="color:#f43f5e">*</span></label>
                    <input type="text" name="name" id="addCustName" data-translate-placeholder="customers.namePlaceholder" placeholder="Customer full name..." required>
                </div>
                <div class="prof-input-group">
                    <label data-translate="customers.phone">Phone</label>
                    <input type="tel" name="phone" id="addCustPhone" data-translate-placeholder="customers.phonePlaceholder" placeholder="Phone number...">
                </div>
                <div class="prof-input-group">
                    <label data-translate="customers.email">Email</label>
                    <input type="email" name="email" id="addCustEmail" data-translate-placeholder="customers.emailPlaceholder" placeholder="Email address...">
                </div>
                <div class="prof-input-group">
                    <label data-translate="customers.address">Address</label>
                    <input type="text" name="address" id="addCustAddress" data-translate-placeholder="customers.addressPlaceholder" placeholder="Address...">
                </div>
            </div>
            <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="toggleAddCustomerPanel()" style="background: #f1f5f9; color: #64748b; padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; border: 1px solid #e2e8f0; cursor: pointer;" data-translate="common.cancel">Cancel</button>
                <button type="submit" class="cust-btn-primary">
                    💾 <span data-translate="customers.saveCustomer">Save Customer</span>
                </button>
            </div>
        </form>
    </div>

    <!-- PART 3: Search Panel -->
    <div class="prof-panel" style="margin-bottom: 30px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 8px;">
                🔍 <span data-translate="customers.search">Search Customers</span>
            </h3>
            <button onclick="resetCustomerSearch()" class="prof-text-btn">
                ♻️ <span data-translate="common.reset">Reset</span>
            </button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="prof-input-group">
                <label data-translate="customers.name">Name</label>
                <input type="text" id="cust-search-name" data-translate-placeholder="customers.searchByName" placeholder="Search by name...">
            </div>
            <div class="prof-input-group">
                <label data-translate="customers.phone">Phone</label>
                <input type="text" id="cust-search-phone" data-translate-placeholder="customers.searchByPhone" placeholder="Search by phone...">
            </div>
            <div class="prof-input-group">
                <label data-translate="customers.email">Email</label>
                <input type="text" id="cust-search-email" data-translate-placeholder="customers.searchByEmail" placeholder="Search by email...">
            </div>
        </div>
        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <button onclick="searchCustomers()" class="prof-btn-primary">
                <span data-translate="customers.searchResults">Search Results</span> ⚡
            </button>
        </div>
    </div>

    <!-- PART 4: Customers Table -->
    <div class="prof-panel" style="padding: 0; overflow: hidden;">
        <div style="padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <span id="cust-results-count" style="font-size: 14px; color: #64748b; font-weight: 600;">
                Showing all 0 customers
            </span>
            <button onclick="loadCustomers()" class="prof-icon-btn" title="Refresh">🔄</button>
        </div>

        <div style="overflow-x: auto;">
            <table id="customersTable" style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                <thead>
                    <tr style="background: #f1f5f9;">
                        <th style="width: 60px; text-align: center;" data-translate="customers.id">ID</th>
                        <th style="width: 200px; text-align: left;" data-translate="customers.name">Name</th>
                        <th style="width: 140px; text-align: left;" data-translate="customers.phone">Phone</th>
                        <th style="width: 200px; text-align: left;" data-translate="customers.email">Email</th>
                        <th style="width: 140px; text-align: right;" data-translate="customers.totalPurchases">Total Purchases</th>
                        <th style="width: 130px; text-align: left;" data-translate="common.createdAt">Joined</th>
                        <th style="width: 110px; text-align: center;" data-translate="common.actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="customersTableBody">
                    <!-- Populated by JS -->
                </tbody>
            </table>
            <div id="noCustomersFound" style="text-align: center; padding: 60px 40px; color: #94a3b8; display: none;">
                <div style="font-size: 48px; margin-bottom: 15px;">🔍</div>
                <p data-translate="customers.noCustomersFound" style="font-size: 18px; font-weight: 600; color: #64748b;">No customers found</p>
            </div>
        </div>
    </div>
</div>

<!-- Bulk SMS Modal -->
<div id="bulkCustomerSmsModal" style="display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); backdrop-filter: blur(6px);">
    <div style="background: white; margin: 5% auto; border-radius: 16px; width: 90%; max-width: 500px; box-shadow: 0 25px 60px rgba(0,0,0,0.15); overflow: hidden; animation: custModalIn 0.3s cubic-bezier(0.4,0,0.2,1);">
        <div style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); color: white; padding: 24px 28px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                💬 <span data-translate="customers.sendMessage">Send Message to All Customers</span>
            </h3>
            <span onclick="closeBulkCustomerSmsModal()" style="color: white; font-size: 26px; line-height: 1; cursor: pointer; opacity: 0.7; transition: opacity 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.7">&times;</span>
        </div>
        <div style="padding: 28px;">
            <form id="bulkCustomerSmsForm" onsubmit="sendBulkCustomerSms(event)">
                <div class="prof-input-group" style="margin-bottom: 20px;">
                    <label data-translate="customers.sendMessage">Message</label>
                    <textarea id="bulkCustomerSmsMessage" required rows="5" placeholder="Write your message for all customers..." style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; resize: vertical; font-family: inherit; font-size: 14px;" data-translate-placeholder="customers.writeMessage"></textarea>
                    <small style="color: #64748b; margin-top: 8px; display: block;">This message will be sent via Twilio WhatsApp to all customers with saved phone numbers.</small>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 18px; border-top: 1px solid #f1f5f9;">
                    <button type="button" onclick="closeBulkCustomerSmsModal()" style="background: #f1f5f9; color: #64748b; padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; border: 1px solid #e2e8f0; cursor: pointer;" data-translate="common.cancel">Cancel</button>
                    <button type="submit" id="btnSendBulkCustomerSms" class="cust-btn-primary" style="background: #4f46e5;">
                        <span data-translate="customers.sendSms">🚀 Send via Twilio</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div id="editCustomerModal" style="display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); backdrop-filter: blur(6px);">
    <div style="background: white; margin: 5% auto; border-radius: 16px; width: 90%; max-width: 560px; box-shadow: 0 25px 60px rgba(0,0,0,0.15); overflow: hidden; animation: custModalIn 0.3s cubic-bezier(0.4,0,0.2,1);">
        <div style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white; padding: 24px 28px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                ✏️ <span data-translate="customers.editCustomer">Edit Customer</span>
            </h3>
            <span onclick="closeEditCustomerModal()" style="color: white; font-size: 26px; line-height: 1; cursor: pointer; opacity: 0.7; transition: opacity 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.7">&times;</span>
        </div>
        <div style="padding: 28px;">
            <form id="editCustomerForm" onsubmit="updateCustomer(event)">
                <input type="hidden" id="editCustomerId" name="id">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px;">
                    <div class="prof-input-group" style="grid-column: span 2;">
                        <label data-translate="customers.name">Name <span style="color:#f43f5e">*</span></label>
                        <input type="text" name="name" id="editCustomerName" required>
                    </div>
                    <div class="prof-input-group">
                        <label data-translate="customers.phone">Phone</label>
                        <input type="tel" name="phone" id="editCustomerPhone">
                    </div>
                    <div class="prof-input-group">
                        <label data-translate="customers.email">Email</label>
                        <input type="email" name="email" id="editCustomerEmail">
                    </div>
                    <div class="prof-input-group" style="grid-column: span 2;">
                        <label data-translate="customers.address">Address</label>
                        <input type="text" name="address" id="editCustomerAddress">
                    </div>
                    <div class="prof-input-group" style="grid-column: span 2;">
                        <label data-translate="customers.totalPurchases">Total Purchases (EGP)</label>
                        <input type="number" name="total_purchases" id="editCustomerTotalPurchases" step="0.01" min="0">
                    </div>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 18px; border-top: 1px solid #f1f5f9;">
                    <button type="button" onclick="closeEditCustomerModal()" style="background: #f1f5f9; color: #64748b; padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; border: 1px solid #e2e8f0; cursor: pointer;" data-translate="common.cancel">Cancel</button>
                    <button type="submit" class="cust-btn-primary" data-translate="customers.updateCustomer">Update Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast Notification Container -->
<div id="custToastContainer" style="position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; pointer-events: none;"></div>

<style>
/* ============================================================
   CUSTOMERS TAB — PROFESSIONAL STYLES (mirrors Sales Tab)
   ============================================================ */

/* Reuse prof-card / prof-panel / prof-input-group from sales_tab if already loaded */
/* These definitions are safe to repeat (identical values) */

#customers table th {
    padding: 16px;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    border-bottom: 2px solid #e2e8f0;
    background: #f8fafc;
}
#customers table td {
    padding: 16px;
    font-size: 14px;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
#customers table tbody tr:hover { background: #f9fafb; }

/* Customer-specific button */
.cust-btn-primary {
    background: #1e293b;
    color: white;
    padding: 12px 22px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 14px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.cust-btn-primary:hover {
    background: #0f172a;
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(0,0,0,0.15);
}

/* Icon action buttons */
.cust-action-btn {
    width: 36px; height: 36px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 15px;
    transition: all 0.2s;
}
.cust-action-btn.edit:hover  { border-color: #6366f1; background: #f5f3ff; }
.cust-action-btn.delete:hover { border-color: #f43f5e; background: #fff1f2; }

/* Stat cards (inherits prof-card colors from sales_tab) */
.prof-card .prof-card-info { display: flex; flex-direction: column; }

/* Modal animation */
@keyframes custModalIn {
    from { opacity: 0; transform: translateY(-30px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0)    scale(1); }
}

/* Toast */
.cust-toast {
    pointer-events: all;
    min-width: 280px;
    max-width: 360px;
    padding: 16px 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 600;
    color: white;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    animation: toastIn 0.35s cubic-bezier(0.4,0,0.2,1);
}
.cust-toast.success { background: linear-gradient(135deg, #059669, #10b981); }
.cust-toast.error   { background: linear-gradient(135deg, #dc2626, #ef4444); }
.cust-toast.info    { background: linear-gradient(135deg, #4f46e5, #6366f1); }
.cust-toast-icon    { font-size: 20px; flex-shrink: 0; }

@keyframes toastIn {
    from { opacity: 0; transform: translateX(40px); }
    to   { opacity: 1; transform: translateX(0); }
}

/* RTL adjustments */
body.rtl .prof-card { border-left: none !important; border-right: 5px solid; }
</style>

<script>
/* ============================================================
   CUSTOMERS TAB — JAVASCRIPT
   ============================================================ */

let allCustomers = [];

/* ---------- init ---------- */
document.addEventListener('DOMContentLoaded', function () {

    // Load when tab becomes active
    const allTabs = document.querySelectorAll('.nav-tab');
    allTabs.forEach(tab => {
        if (tab.textContent.includes('Customer') ||
            tab.getAttribute('data-translate') === 'navigation.customers') {
            tab.addEventListener('click', () => setTimeout(loadCustomers, 120));
        }
    });

    // Load immediately if already visible
    const tabEl = document.getElementById('customers');
    if (tabEl && (tabEl.classList.contains('active') || tabEl.style.display !== 'none')) {
        loadCustomers();
    }

    // Keyboard search
    ['cust-search-name','cust-search-phone','cust-search-email'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('keyup', e => { if (e.key === 'Enter') searchCustomers(); });
    });
});

/* ---------- toast ---------- */
function custShowToast(message, type = 'success', duration = 3500) {
    const icons = { success: '✅', error: '❌', info: 'ℹ️' };
    const container = document.getElementById('custToastContainer');
    const toast = document.createElement('div');
    toast.className = `cust-toast ${type}`;
    toast.innerHTML = `<span class="cust-toast-icon">${icons[type] || '🔔'}</span><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.transition = 'opacity 0.4s, transform 0.4s';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(40px)';
        setTimeout(() => toast.remove(), 400);
    }, duration);
}

/* ---------- add customer panel ---------- */
function toggleAddCustomerPanel() {
    const panel = document.getElementById('addCustomerPanel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    if (panel.style.display === 'block') {
        document.getElementById('addCustName').focus();
    }
}

/* ---------- load customers ---------- */
function loadCustomers() {
    fetch('api/customers.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                allCustomers = data.data;
                displayCustomers(allCustomers);
                updateStatCards(allCustomers);
            }
        })
        .catch(() => custShowToast('Failed to load customers.', 'error'));
}

/* ---------- update stat cards ---------- */
function updateStatCards(customers) {
    const now = new Date();
    const thisMonthYear = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;

    const total   = customers.length;
    const revenue = customers.reduce((s, c) => s + (parseFloat(c.total_purchases) || 0), 0);
    const newCount = customers.filter(c => {
        if (!c.created_at) return false;
        const d = c.created_at.substring(0, 7);
        return d === thisMonthYear;
    }).length;

    let topCustomer = { name: '—', total_purchases: 0 };
    customers.forEach(c => {
        if ((parseFloat(c.total_purchases) || 0) > topCustomer.total_purchases) {
            topCustomer = c;
        }
    });

    document.getElementById('cust-stat-total').textContent   = total;
    document.getElementById('cust-stat-revenue').innerHTML   = `${formatCurrency ? formatCurrency(revenue).replace(' EGP','') : revenue.toFixed(2)} <small style="font-size:14px">EGP</small>`;
    document.getElementById('cust-stat-new').textContent     = newCount;
    document.getElementById('cust-stat-top').textContent     = topCustomer.name || '—';
    document.getElementById('customerCount') && (document.getElementById('customerCount').textContent = total);
}

/* ---------- display table ---------- */
function displayCustomers(customers) {
    const tbody = document.getElementById('customersTableBody');
    const noDiv = document.getElementById('noCustomersFound');
    const countEl = document.getElementById('cust-results-count');

    countEl.textContent = `Showing ${customers.length} customer${customers.length !== 1 ? 's' : ''}`;

    if (customers.length === 0) {
        tbody.innerHTML = '';
        noDiv.style.display = 'block';
        return;
    }
    noDiv.style.display = 'none';

    tbody.innerHTML = customers.map(c => {
        const purchases = parseFloat(c.total_purchases) || 0;
        const joined = c.created_at
            ? new Date(c.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
            : '—';

        const tierColor = purchases >= 50000 ? '#7c3aed' : purchases >= 10000 ? '#0ea5e9' : '#10b981';
        const tierBg    = purchases >= 50000 ? '#f5f3ff' : purchases >= 10000 ? '#e0f2fe' : '#ecfdf5';

        return `
        <tr>
            <td style="text-align:center; font-weight:600; color:#94a3b8;">${c.id}</td>
            <td>
                <div style="font-weight:700; color:#1e293b;">${escHtml(c.name)}</div>
                <div style="font-size:11px; color:#94a3b8;">${escHtml(c.email || '')}</div>
            </td>
            <td style="color:#475569;">${escHtml(c.phone || '—')}</td>
            <td style="color:#475569; font-size:13px;">${escHtml(c.email || '—')}</td>
            <td style="text-align:right;">
                <span style="background:${tierBg}; color:${tierColor}; padding:5px 12px; border-radius:6px; font-weight:800; font-size:13px; border:1px solid ${tierColor}22;">
                    ${typeof formatCurrency === 'function' ? formatCurrency(purchases) : purchases.toFixed(2) + ' EGP'}
                </span>
            </td>
            <td style="color:#64748b; font-size:13px;">${joined}</td>
            <td style="text-align:center;">
                <div style="display:flex; gap:6px; justify-content:center;">
                    <button class="cust-action-btn edit" onclick="editCustomer(${c.id})" title="Edit">✏️</button>
                    <button class="cust-action-btn delete" onclick="deleteCustomer(${c.id})" title="Delete">🗑️</button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

/* ---------- search ---------- */
function searchCustomers() {
    const name  = document.getElementById('cust-search-name').value.toLowerCase().trim();
    const phone = document.getElementById('cust-search-phone').value.toLowerCase().trim();
    const email = document.getElementById('cust-search-email').value.toLowerCase().trim();

    const filtered = allCustomers.filter(c =>
        (!name  || c.name.toLowerCase().includes(name)) &&
        (!phone || (c.phone && c.phone.toLowerCase().includes(phone))) &&
        (!email || (c.email && c.email.toLowerCase().includes(email)))
    );
    displayCustomers(filtered);
}

function resetCustomerSearch() {
    document.getElementById('cust-search-name').value  = '';
    document.getElementById('cust-search-phone').value = '';
    document.getElementById('cust-search-email').value = '';
    displayCustomers(allCustomers);
}

/* ---------- add customer ---------- */
function addCustomer(event) {
    event.preventDefault();
    const form = document.getElementById('addCustomerForm');
    const data = {
        name:    document.getElementById('addCustName').value.trim(),
        phone:   document.getElementById('addCustPhone').value.trim() || null,
        email:   document.getElementById('addCustEmail').value.trim() || null,
        address: document.getElementById('addCustAddress').value.trim() || null,
    };

    if (!data.name) { custShowToast('Customer name is required.', 'error'); return; }

    fetch('api/customers.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            if (result.action === 'use_existing') {
                custShowToast(`Customer already exists: ${result.customer_name}`, 'info');
            } else {
                custShowToast(`Customer "${result.customer_name}" added successfully! 🎉`, 'success');
            }
            form.reset();
            toggleAddCustomerPanel();
            loadCustomers();
        } else {
            custShowToast('Error: ' + result.message, 'error');
        }
    })
    .catch(() => custShowToast('Error adding customer. Please try again.', 'error'));
}

/* ---------- edit customer ---------- */
function editCustomer(id) {
    const c = allCustomers.find(x => x.id === id);
    if (!c) return;

    document.getElementById('editCustomerId').value             = c.id;
    document.getElementById('editCustomerName').value           = c.name;
    document.getElementById('editCustomerPhone').value          = c.phone || '';
    document.getElementById('editCustomerEmail').value          = c.email || '';
    document.getElementById('editCustomerAddress').value        = c.address || '';
    document.getElementById('editCustomerTotalPurchases').value = c.total_purchases || 0;

    document.getElementById('editCustomerModal').style.display = 'block';
}

function closeEditCustomerModal() {
    document.getElementById('editCustomerModal').style.display = 'none';
}

function updateCustomer(event) {
    event.preventDefault();
    const id   = document.getElementById('editCustomerId').value;
    const data = {
        id,
        name:            document.getElementById('editCustomerName').value.trim(),
        phone:           document.getElementById('editCustomerPhone').value.trim() || null,
        email:           document.getElementById('editCustomerEmail').value.trim() || null,
        address:         document.getElementById('editCustomerAddress').value.trim() || null,
        total_purchases: parseFloat(document.getElementById('editCustomerTotalPurchases').value) || 0
    };

    fetch(`api/customers.php?id=${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            custShowToast('Customer updated successfully! ✅', 'success');
            closeEditCustomerModal();
            loadCustomers();
        } else {
            custShowToast('Error: ' + result.message, 'error');
        }
    })
    .catch(() => custShowToast('Error updating customer.', 'error'));
}

/* ---------- delete customer ---------- */
function deleteCustomer(id) {
    const c = allCustomers.find(x => x.id === id);
    const name = c ? c.name : `#${id}`;

    // Styled confirmation using a quick toast-style overlay
    if (!confirm(`Delete customer "${name}"?\n\nThis action cannot be undone.`)) return;

    fetch(`api/customers.php?id=${id}`, { method: 'DELETE' })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            custShowToast(`Customer "${name}" deleted.`, 'success');
            loadCustomers();
        } else {
            custShowToast('Error: ' + result.message, 'error');
        }
    })
    .catch(() => custShowToast('Error deleting customer.', 'error'));
}

/* ---------- helpers ---------- */
function escHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Close modals on outside click
window.addEventListener('click', function(e) {
    const editModal = document.getElementById('editCustomerModal');
    if (e.target === editModal) closeEditCustomerModal();
    
    const bulkSmsModal = document.getElementById('bulkCustomerSmsModal');
    if (e.target === bulkSmsModal) closeBulkCustomerSmsModal();
});

/* ---------- bulk sms ---------- */
function openBulkCustomerSmsModal() {
    document.getElementById('bulkCustomerSmsModal').style.display = 'block';
    setTimeout(() => {
        document.getElementById('bulkCustomerSmsMessage').focus();
    }, 100);
}

function closeBulkCustomerSmsModal() {
    document.getElementById('bulkCustomerSmsModal').style.display = 'none';
    document.getElementById('bulkCustomerSmsForm').reset();
}

function sendBulkCustomerSms(event) {
    event.preventDefault();
    const btn = document.getElementById('btnSendBulkCustomerSms');
    const message = document.getElementById('bulkCustomerSmsMessage').value.trim();
    
    if (!message) {
        custShowToast('Please write a message first.', 'warning');
        return;
    }
    
    if (!confirm('Are you sure you want to send this message to all customers with saved phone numbers?')) {
        return;
    }
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Sending...';
    btn.disabled = true;
    
    fetch('api/send_customer_sms.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: message })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            custShowToast(data.message || 'Messages sent successfully! 🎉', 'success', 5000);
            closeBulkCustomerSmsModal();
        } else {
            custShowToast(data.message || 'Error sending messages.', 'error', 6000);
            if (data.stats && data.stats.success > 0) {
                // Partial success
                setTimeout(() => closeBulkCustomerSmsModal(), 2000);
            }
        }
    })
    .catch(err => {
        console.error('Bulk SMS error:', err);
        custShowToast('Network error while sending messages.', 'error');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}
</script>
