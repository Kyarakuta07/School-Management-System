<!-- Rename Modal -->
<div class="modal-overlay" id="rename-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Rename Pet</h3>
            <button class="modal-close" onclick="closeRenameModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="text" class="form-input" id="new-name-input" placeholder="Enter new name" maxlength="50">
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeRenameModal()">Cancel</button>
            <button class="btn-primary" onclick="savePetName()">Save</button>
        </div>
    </div>
</div>

<!-- Item Use Modal -->
<div class="modal-overlay" id="item-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="item-modal-title">Use Item</h3>
            <button class="modal-close" onclick="closeItemModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="item-list" id="item-list">
                <!-- Items rendered by JS -->
            </div>
        </div>
    </div>
</div>

<!-- Shop Buy Modal (Legacy) -->
<div class="modal-overlay" id="shop-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Buy Item</h3>
            <button class="modal-close" onclick="closeShopModal()">&times;</button>
        </div>
        <div class="modal-body" id="shop-modal-body">
            <!-- Item details rendered by JS -->
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeShopModal()">Cancel</button>
            <button class="btn-primary" id="shop-buy-btn">Buy</button>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal-overlay" id="help-modal">
    <div class="modal-content" style="max-width: 500px; max-height: 80vh;">
        <div class="modal-header">
            <h3 class="modal-title">Help & Tutorial</h3>
            <button class="modal-close" onclick="closeHelpModal()">&times;</button>
        </div>
        <div class="modal-body" style="overflow-y: auto;">
            <div class="help-section">
                <h4 style="color: var(--gold); margin-bottom: 12px;"><i class="fas fa-paw"></i> Getting Started</h4>
                <p style="color: #aaa; line-height: 1.6; margin-bottom: 16px;">
                    Get your first pet from the Gacha tab! Use gold to roll for random pets with different elements
                    and rarities.
                </p>
            </div>
            <div class="help-section">
                <h4 style="color: var(--gold); margin-bottom: 12px;"><i class="fas fa-heart"></i> Taking Care</h4>
                <p style="color: #aaa; line-height: 1.6; margin-bottom: 16px;">
                    Feed your pet to restore hunger, play to boost mood, and heal when health is low. Stats decay
                    over time!
                </p>
            </div>
            <div class="help-section">
                <h4 style="color: var(--gold); margin-bottom: 12px;"><i class="fas fa-shield-alt"></i> Battle</h4>
                <p style="color: #aaa; line-height: 1.6;">
                    Enter the Arena to battle other pets! Win to earn EXP, gold, and level up your companion.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Sell Pet Modal -->
<div class="modal-overlay" id="sell-modal">
    <div class="modal-content" style="max-width: 320px;">
        <div class="modal-header">
            <h3 class="modal-title">Sell Pet</h3>
            <button class="modal-close" onclick="closeSellModal()">&times;</button>
        </div>
        <div class="modal-body" style="text-align: center; padding: 16px;">
            <img src="" alt="" id="sell-pet-img"
                style="width: 70px; height: 70px; object-fit: contain; margin-bottom: 8px;">
            <h3 id="sell-pet-name" style="color: #fff; margin-bottom: 2px; font-size: 1rem;"></h3>
            <span id="sell-pet-level" style="color: var(--gold); font-size: 0.8rem;"></span>
            <div
                style="margin-top: 12px; padding: 12px; background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); border-radius: 10px;">
                <p style="color: #888; font-size: 0.75rem; margin-bottom: 6px;">You will receive:</p>
                <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="fas fa-coins" style="color: #FFD700; font-size: 1rem;"></i>
                    <span id="sell-price" style="color: #FFD700; font-size: 1.2rem; font-weight: 700;"></span>
                    <span style="color: #888; font-size: 0.85rem;">Gold</span>
                </div>
            </div>
            <p style="color: #E74C3C; font-size: 0.7rem; margin-top: 10px;">
                <i class="fas fa-exclamation-triangle"></i> This action cannot be undone!
            </p>
        </div>
        <div class="modal-footer" style="padding: 12px; gap: 8px;">
            <button class="btn-secondary" onclick="closeSellModal()"
                style="padding: 8px 16px; font-size: 0.8rem;">Cancel</button>
            <button class="btn-primary" id="confirm-sell-btn" onclick="confirmSellPet()"
                style="background: linear-gradient(135deg, #E74C3C, #C0392B); padding: 8px 16px; font-size: 0.8rem;">
                <i class="fas fa-check"></i> Confirm
            </button>
        </div>
    </div>
</div>