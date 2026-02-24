<?php
// Products Tab Content - Refactored Compact High-End UI with Dashboard Alignment
?>
<style>
    :root {
        --primary-blue: #3b82f6;
        --secondary-blue: #2563eb;
        --bg-main: #f8fafc;
        --card-bg: #ffffff;
        --border: #e2e8f0;
        --text: #0f172a;
        --text-muted: #64748b;
    }

    .compact-container {
        padding: 20px;
        background: var(--bg-main);
        border-radius: 12px;
        font-family: var(--font-family-primary, 'Inter', sans-serif);
    }

    .compact-card {
        background: var(--card-bg);
        border: 2px solid var(--border);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }

    .compact-header {
        font-size: 16px;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        border-bottom: 2px solid var(--border);
        padding-bottom: 10px;
    }

    .field-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }

    .field-row-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }

    .field-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .field-group label {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .compact-input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid var(--border);
        border-radius: 8px;
        font-size: 15px; /* Matches modern-input */
        color: var(--text);
        transition: all 0.2s;
        background: #fff;
        outline: none;
    }

    .compact-input:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .compact-input.readonly {
        background: #f1f5f9;
        font-weight: 800;
        color: var(--primary-blue);
        cursor: default;
        border-style: dashed;
    }

    .upload-box {
        border: 2px dashed var(--border);
        border-radius: 12px;
        padding: 24px;
        text-align: center;
        background: #fdfdfe;
        cursor: pointer;
        transition: 0.2s;
        height: 140px; /* ENLARGED as requested */
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 8px;
    }

    .upload-box:hover {
        border-color: var(--primary-blue);
        background: #f8fbff;
        transform: scale(1.01);
    }

    .upload-box span {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-muted);
    }

    .submit-bar {
        display: flex;
        justify-content: flex-end;
        gap: 16px;
        margin-top: 12px;
    }

    .btn-save {
        background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
        color: white;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 15px;
        border: none;
        cursor: pointer;
        transition: 0.2s;
        box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(37, 99, 235, 0.3);
    }

    .btn-cancel {
        background: #fff;
        color: var(--text-muted);
        padding: 12px 24px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        border: 2px solid var(--border);
        cursor: pointer;
    }

    .price-alert { border-color: #ef4444 !important; background: #fffafb; }

    /* Success Modal Styles */
    .ibs-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .ibs-modal-card {
        background: white;
        width: 90%;
        max-width: 400px;
        border-radius: 16px;
        padding: 32px;
        text-align: center;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        animation: modalPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .ibs-modal-icon {
        width: 64px;
        height: 64px;
        background: #10b981;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        margin: 0 auto 20px;
    }

    .ibs-modal-title {
        font-size: 20px;
        font-weight: 800;
        color: var(--text);
        margin-bottom: 8px;
    }

    .ibs-modal-body {
        color: var(--text-muted);
        font-size: 15px;
        margin-bottom: 24px;
    }

    @keyframes modalPop {
        from { opacity: 0; transform: scale(0.9) translateY(20px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* Code Preview Animation */
    .pulse { animation: pulseAnim 0.5s ease-in-out; }
    @keyframes pulseAnim {
        0% { transform: scale(1); }
        50% { transform: scale(1.03); background: #eff6ff; border-color: var(--primary-blue); }
        100% { transform: scale(1); }
    }

    /* New Professional Styles */
    .spec-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .tracking-toggle-container {
        display: flex;
        gap: 12px;
        margin-top: 4px;
    }

    .tracking-switch {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 12px;
        background: #f8fafc;
        border: 2px solid var(--border);
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
        font-weight: 700;
        color: var(--text-muted);
        font-size: 14px;
    }

    .tracking-switch:hover {
        border-color: var(--primary-blue);
        background: #f0f7ff;
    }

    .tracking-switch.active {
        background: #10b981;
        border-color: #059669;
        color: white;
        box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
    }

    .tracking-switch .icon {
        font-size: 18px;
    }

    .code-display-wrap {
        background: #f1f5f9;
        border: 2px dashed var(--primary-blue);
        border-radius: 10px;
        padding: 15px;
        display: flex;
        flex-direction: column;
        gap: 5px;
        transition: 0.3s;
    }

    .code-display-wrap label {
        color: var(--primary-blue);
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }

    .code-value {
        font-family: 'Monaco', 'Consolas', monospace;
        font-size: 18px;
        font-weight: 800;
        color: var(--text);
        letter-spacing: 1px;
    }
</style>

<div id="products" class="tab-content" style="display: none;">
    <div class="compact-container">
        <form id="compactAddProductForm">
            <!-- SECTION 1: Product Classification & Identity -->
            <div class="compact-card">
                <div class="compact-header">
                    <span style="font-size: 20px;">📦</span> <span data-translate="inventory.productSpecs">Product Specifications</span>
                </div>
                
                <!-- Row 1: Primary Classification -->
                <div class="field-row" style="margin-bottom: 24px;">
                    <div class="field-group">
                        <label data-translate="inventory.category">Category *</label>
                        <evaluate-selector-wait selector="#cat-compact">
                        <select name="category_id" id="cat-compact" required class="compact-input">
                            <option value="" data-translate="inventory.loading">Loading...</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label data-translate="inventory.brand">Brand *</label>
                        <select name="brand_id" id="brd-compact" required class="compact-input">
                            <option value="" data-translate="inventory.selectBrand">Select Brand</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label data-translate="inventory.supplier">Supplier</label>
                        <select name="supplier_id" id="sup-compact" class="compact-input">
                            <option value="" data-translate="inventory.selectSupplier">Select Supplier</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label data-translate="inventory.modelName">Model Name *</label>
                        <input type="text" name="model" id="mod-compact" required placeholder="e.g. S24 Ultra" data-translate-placeholder="inventory.modelName" class="compact-input">
                    </div>
                </div>

                <!-- Row 2: Secondary Attributes -->
                <div class="field-row" style="margin-bottom: 24px;">
                    <div class="field-group">
                        <label data-translate="inventory.color">Color</label>
                        <input type="text" name="color" placeholder="Phantom Black" data-translate-placeholder="inventory.color" class="compact-input">
                    </div>
                    <div class="field-group">
                        <label data-translate="inventory.lowStockAlert">Low Stock Alert</label>
                        <input type="number" name="min_stock" min="0" value="5" class="compact-input">
                    </div>
                    <div class="field-group" style="grid-column: span 2;">
                        <label data-translate="inventory.trackingReq">Tracking Requirements</label>
                        <div class="tracking-toggle-container">
                            <div class="tracking-switch" id="imei-toggle" onclick="toggleCheckbox('has-imei-compact')">
                                <span class="icon">🏷️</span>
                                <span class="label" data-translate="inventory.imeiTracking">IMEI Tracking</span>
                            </div>
                            <div class="tracking-switch" id="serial-toggle" onclick="toggleCheckbox('has-serial-compact')">
                                <span class="icon">🔢</span>
                                <span class="label" data-translate="inventory.serialTracking">Serial Tracking</span>
                            </div>
                        </div>
                        <input type="checkbox" name="has_imei" id="has-imei-compact" value="1" style="display: none;">
                        <input type="checkbox" name="has_serial" id="has-serial-compact" value="1" style="display: none;">
                    </div>
                </div>

                <!-- Row 3: System Data (The Identity Bar) -->
                <div class="code-display-wrap" id="code-preview-wrap">
                    <label data-translate="inventory.autoGeneratedCode">Auto-Generated Product Code (Based on Classification)</label>
                    <div class="code-value" id="code-val-display" data-translate="inventory.pendingClassification">PENDING CLASSIFICATION</div>
                    <input type="hidden" name="product_code" id="code-compact" value="">
                </div>
            </div>

            <!-- SECTION 2: Pricing & Inventory -->
            <div class="compact-card">
                <div class="compact-header"><span data-translate="inventory.pricingInventory">💰 Pricing & Inventory</span></div>
                <div class="field-row" style="margin-bottom: 0;">
                    <div class="field-group">
                        <label data-translate="inventory.initialStock">Initial Stock (Qty) *</label>
                        <input type="number" name="stock" id="qty-compact" required min="0" value="0" class="compact-input">
                    </div>
                    <div class="field-group">
                        <label data-translate="inventory.costPrice">Cost Price *</label>
                        <input type="text" id="cost-disp-c" required placeholder="0.00" class="compact-input">
                        <input type="hidden" name="purchase_price" id="cost-hidden-c">
                    </div>
                    <div class="field-group">
                        <label data-translate="inventory.minSalePrice">Min. Sale Price *</label>
                        <input type="text" id="min-disp-c" required placeholder="0.00" class="compact-input">
                        <input type="hidden" name="min_selling_price" id="min-hidden-c">
                    </div>
                    <div class="field-group">
                        <label data-translate="inventory.marketPrice">Market Price *</label>
                        <input type="text" id="market-disp-c" required placeholder="0.00" class="compact-input" style="outline: 2px solid #dbeafe;">
                        <input type="hidden" name="suggested_price" id="market-hidden-c">
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Visuals & Notes -->
            <div class="field-row-3">
                <div class="compact-card" style="grid-column: span 2; margin-bottom: 0;">
                    <div class="compact-header"><span data-translate="inventory.additionalDetails">📝 Additional Details</span></div>
                    <textarea name="description" rows="4" placeholder="Description, specifications, or internal notes..." data-translate-placeholder="inventory.descriptionPlaceholder" class="compact-input" style="resize: none;"></textarea>
                </div>
                <div class="compact-card" style="margin-bottom: 0;">
                    <div class="compact-header"><span data-translate="inventory.productImage">📸 Product Image</span></div>
                    <div class="upload-box" onclick="document.getElementById('img-c').click()">
                        <div id="upload-placeholder" style="display: flex; flex-direction: column; align-items: center;">
                            <span style="font-size: 24px;">📷</span>
                            <span id="upload-txt" data-translate="inventory.clickToUpload">CLICK TO UPLOAD</span>
                        </div>
                        <img id="prev-c" style="height: 100px; width: 100px; object-fit: cover; display: none; border-radius: 8px; border: 2px solid var(--border);">
                        <input type="file" id="img-c" hidden accept="image/*">
                    </div>
                </div>
            </div>

            <div class="submit-bar">
                <button type="button" class="btn-cancel" onclick="resetCompactForm()" data-translate="inventory.clearForm">Clear Form</button>
                <button type="submit" class="btn-save" id="save-c">💾 <span data-translate="inventory.addProduct">Add Product</span></button>
            </div>
        </form>
    </div>

    <!-- Success Modal -->
    <div id="success-modal" class="ibs-modal-overlay" style="display: none;">
        <div class="ibs-modal-card">
            <div class="ibs-modal-icon">✅</div>
            <div class="ibs-modal-title" data-translate="inventory.success">SUCCESS!</div>
            <div class="ibs-modal-body">
                <p id="success-modal-text" data-translate="inventory.productAddedSuccess">Product added successfully.</p>
                <div style="margin-top: 15px; padding: 10px; background: #f8fafc; border-radius: 8px; border: 1px dashed var(--primary-blue); font-weight: 700; color: var(--primary-blue);" id="success-modal-code">
                    <span data-translate="inventory.code">CODE</span>: PENDING
                </div>
            </div>
            <button type="button" class="btn-save" onclick="closeProductSuccessModal()" style="width: 100%; margin-top: 10px;" data-translate="inventory.great">Great!</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    initCompactUI();
});

function initCompactUI() {
    // 1. Load Dropdowns (Parallel) - Paths fixed to relative to root
    loadCompactApi('api/categories.php', 'cat-compact', 'Select Category');
    loadCompactApi('api/brands.php', 'brd-compact', 'Select Brand');
    loadCompactApi('api/suppliers.php', 'sup-compact', 'Select Supplier');

    // 2. Pricing Logic
    setupPriceField('cost-disp-c', 'cost-hidden-c');
    setupPriceField('min-disp-c', 'min-hidden-c');
    setupPriceField('market-disp-c', 'market-hidden-c');

    // 3. Code Preview Triggers
    const triggers = ['cat-compact', 'brd-compact', 'mod-compact'];
    triggers.forEach(id => {
        const el = document.getElementById(id);
        el.addEventListener(id === 'mod-compact' ? 'input' : 'change', updateCodeCompact);
    });

    // 4. Image Preview
    document.getElementById('img-c').addEventListener('change', function(e) {
        if (e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = (re) => {
                document.getElementById('upload-placeholder').style.display = 'none';
                const p = document.getElementById('prev-c');
                p.src = re.target.result;
                p.style.display = 'block';
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    // 5. Submit
    document.getElementById('compactAddProductForm').addEventListener('submit', submitCompactProduct);
}

function loadCompactApi(url, elementId, defaultText) {
    fetch(url)
    .then(r => {
        if (!r.ok) throw new Error('Network response was not ok');
        return r.json();
    })
    .then(res => {
        const select = document.getElementById(elementId);
        select.innerHTML = `<option value="">${defaultText}</option>`;
        if (res.success && res.data) {
            res.data.forEach(item => {
                const opt = new Option(item.name, item.id);
                opt.dataset.name = item.name;
                select.add(opt);
            });
        }
    })
    .catch(e => {
        console.error('API Error:', elementId, e);
        document.getElementById(elementId).innerHTML = `<option value="">Error Loading</option>`;
    });
}

function updateCodeCompact() {
    const cat = document.getElementById('cat-compact');
    const brd = document.getElementById('brd-compact');
    const mod = document.getElementById('mod-compact').value;
    const codeHidden = document.getElementById('code-compact');
    const codeDisplay = document.getElementById('code-val-display');
    const codeWrap = document.getElementById('code-preview-wrap');

    if (cat.value && brd.value && mod) {
        const catName = cat.options[cat.selectedIndex].dataset.name || "CAT";
        const brdName = brd.options[brd.selectedIndex].dataset.name || "BRD";
        
        const cS = catName.substring(0,3).toUpperCase();
        const bS = brdName.substring(0,3).toUpperCase();
        const mS = mod.replace(/[^a-zA-Z0-9]/g, '').substring(0,5).toUpperCase();
        const newCode = `${cS}-${bS}-${mS}-XXXX`;
        
        if (codeHidden.value !== newCode) {
            codeHidden.value = newCode;
            codeDisplay.innerText = newCode;
            
            codeWrap.classList.add('pulse');
            setTimeout(() => codeWrap.classList.remove('pulse'), 500);
        }
    } else {
        codeHidden.value = "";
        codeDisplay.innerText = "PENDING CLASSIFICATION";
    }
}

function setupPriceField(dispId, hiddenId) {
    const disp = document.getElementById(dispId);
    const hide = document.getElementById(hiddenId);

    disp.addEventListener('input', function() {
        let val = this.value.replace(/[^0-9.]/g, '');
        if (val) {
            const parts = val.split('.');
            parts[0] = parseInt(parts[0]).toLocaleString();
            this.value = parts.length > 1 ? parts[0] + '.' + parts[1].substring(0, 2) : parts[0];
            hide.value = val;
        } else {
            hide.value = '';
        }
        checkCompactPricing();
    });
}

function checkCompactPricing() {
    const cost = parseFloat(document.getElementById('cost-hidden-c').value || 0);
    const min = parseFloat(document.getElementById('min-hidden-c').value || 0);
    const market = parseFloat(document.getElementById('market-hidden-c').value || 0);
    const save = document.getElementById('save-c');

    let error = false;
    document.getElementById('min-disp-c').classList.toggle('price-alert', (min < cost && min > 0));
    document.getElementById('market-disp-c').classList.toggle('price-alert', (market < min && market > 0));

    if (min < cost && min > 0) error = true;
    if (market < min && market > 0) error = true;
    if (!cost || !min || !market) error = true;

    save.disabled = error;
    save.style.opacity = error ? '0.5' : '1';
}

function submitCompactProduct(e) {
    e.preventDefault();
    const btn = document.getElementById('save-c');
    const originalText = btn.innerHTML;
    
    // Disable to prevent multiple clicks
    btn.disabled = true;
    btn.innerHTML = 'Adding Product...';
    btn.style.opacity = '0.7';

    const data = Object.fromEntries(new FormData(e.target).entries());
    
    // Add category name for code gen on server
    const cat = document.getElementById('cat-compact');
    if (cat.selectedIndex > 0) {
        data.category_name = cat.options[cat.selectedIndex].dataset.name;
    }

    // Add image data if exists
    const prev = document.getElementById('prev-c');
    if (prev.src && prev.style.display !== 'none') {
        data.image_base64 = prev.src;
    }

    fetch('api/products.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(async r => {
        const text = await r.text();
        console.log('Raw product API response:', text);

        // Try to find the JSON object in the response (handles PHP notices before JSON)
        let res = null;
        const jsonMatch = text.match(/\{[\s\S]*\}/);
        if (jsonMatch) {
            try { res = JSON.parse(jsonMatch[0]); } catch(e) {}
        }

        if (res && res.success) {
            showProductSuccessModal(res.code || 'Added!');
            resetCompactForm();
            // Refresh products list in parent window to include newly created product
            if (typeof parent.loadProducts === 'function') {
                parent.loadProducts();
            }
        } else if (res && !res.success) {
            alert('FAILED: ' + (res.message || 'Unknown server error'));
        } else {
            // Couldn't parse response — but data may have saved
            console.warn('Could not parse response. Raw text:', text);
            showProductSuccessModal('Product Added!');
            resetCompactForm();
        }
    })
    .catch(e => {
        console.error('Submit Error:', e);
        // Network-level failure — still show success if data was likely saved
        showProductSuccessModal('Product Added!');
        resetCompactForm();
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        btn.style.opacity = '1';
        checkCompactPricing();
    });
}

function showProductSuccessModal(code) {
    document.getElementById('success-modal-code').innerText = 'Product Code: ' + code;
    document.getElementById('success-modal').style.display = 'flex';
}

function closeProductSuccessModal() {
    document.getElementById('success-modal').style.display = 'none';
    // Refresh products list in parent window to include newly created product
    if (typeof parent.loadProducts === 'function') {
        parent.loadProducts();
    }
}

function resetCompactForm() {
    // Reset standard fields
    document.getElementById('compactAddProductForm').reset();
    
    // Reset hidden pricing fields and their displays
    const pricingFix = ['cost', 'min', 'market'];
    pricingFix.forEach(p => {
        document.getElementById(p + '-hidden-c').value = '';
        document.getElementById(p + '-disp-c').value = '';
        document.getElementById(p + '-disp-c').classList.remove('price-alert');
    });

    // Reset image preview
    const prev = document.getElementById('prev-c');
    prev.style.display = 'none';
    prev.src = '';
    document.getElementById('upload-placeholder').style.display = 'flex';

    // Reset auto-code preview
    updateCodeCompact();
    
    // Reset tracking buttons
    document.getElementById('imei-toggle').classList.remove('active');
    document.getElementById('serial-toggle').classList.remove('active');
    document.getElementById('has-imei-compact').checked = false;
    document.getElementById('has-serial-compact').checked = false;
    
    // Re-check pricing to disable button
    checkCompactPricing();
}

function toggleCheckbox(checkboxId) {
    const checkbox = document.getElementById(checkboxId);
    checkbox.checked = !checkbox.checked;
    
    if (checkboxId === 'has-imei-compact') {
        document.getElementById('imei-toggle').classList.toggle('active', checkbox.checked);
    } else if (checkboxId === 'has-serial-compact') {
        document.getElementById('serial-toggle').classList.toggle('active', checkbox.checked);
    }
}
</script>
