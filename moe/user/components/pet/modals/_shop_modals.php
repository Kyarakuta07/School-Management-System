<!-- Shop Purchase Modal -->
<div class="modal" id="shop-purchase-modal">
    <div class="modal-backdrop" onclick="closeShopPurchaseModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-shopping-cart"></i>
                Purchase Item
            </h3>
            <button class="modal-close" onclick="closeShopPurchaseModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <img id="shop-modal-img" src="" alt="Item">
            <h4 id="shop-modal-name">Item Name</h4>
            <p id="shop-modal-desc">Item description</p>

            <div class="shop-qty-controls">
                <button class="qty-btn" onclick="adjustShopQty(-1)">-</button>
                <input type="number" id="shop-qty-input" value="1" min="1" max="99" onchange="updateShopTotal()">
                <button class="qty-btn" onclick="adjustShopQty(1)">+</button>
            </div>

            <div class="shop-price-summary">
                <div class="price-row">
                    <span class="price-label">Unit Price:</span>
                    <span class="price-value">
                        <i class="fas fa-coins"></i>
                        <span id="shop-unit-price">0</span>
                    </span>
                </div>
                <div class="price-row">
                    <span class="price-label">Total:</span>
                    <span class="price-value">
                        <i class="fas fa-coins"></i>
                        <span id="shop-total-price">0</span>
                    </span>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button class="modal-btn-cancel" onclick="closeShopPurchaseModal()">Cancel</button>
            <button class="modal-btn-confirm" onclick="confirmShopPurchase()">
                <i class="fas fa-check"></i>
                Confirm Purchase
            </button>
        </div>
    </div>
</div>

<!-- Revive Pet Modal -->
<div class="modal" id="revive-modal">
    <div class="modal-backdrop" onclick="closeReviveModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-heart"></i>
                Revive Pet
            </h3>
            <button class="modal-close" onclick="closeReviveModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p style="text-align: center; color: rgba(255,255,255,0.8); margin-bottom: 1.5rem;">
                Select a dead pet to revive:
            </p>
            <div id="dead-pets-list" class="dead-pets-grid">
                <!-- Dead pets will be rendered here by JS -->
            </div>
        </div>
    </div>
</div>

<!-- Bulk Use Modal -->
<div class="modal" id="bulk-use-modal">
    <div class="modal-backdrop" onclick="closeBulkModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-boxes"></i>
                <span id="bulk-modal-title">Use Item</span>
            </h3>
            <button class="modal-close" onclick="closeBulkModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <img id="bulk-item-img" src="" alt="Item"
                style="width: 100px; height: 100px; object-fit: contain; margin: 1rem auto; display: block;">
            <p id="bulk-item-desc" style="text-align: center; color: rgba(255,255,255,0.7); margin-bottom: 1.5rem;">
            </p>

            <div class="shop-qty-controls">
                <button class="qty-btn" onclick="adjustQty(-1)">-</button>
                <input type="number" id="bulk-item-qty" value="1" min="1" max="99">
                <button class="qty-btn" onclick="adjustQty(1)">+</button>
            </div>
        </div>
        <div class="modal-actions">
            <button class="modal-btn-cancel" onclick="closeBulkModal()">Cancel</button>
            <button class="modal-btn-confirm" onclick="confirmBulkUse()">
                <i class="fas fa-check"></i>
                Use Item
            </button>
        </div>
    </div>
</div>