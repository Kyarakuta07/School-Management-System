<!-- Rename Modal -->
<div class="modal" id="rename-modal">
    <div class="modal-backdrop" onclick="closeRenameModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-edit"></i>
                Rename Pet
            </h3>
            <button class="modal-close" onclick="closeRenameModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p style="color: #888; font-size: 0.9rem; margin-bottom: 1rem;">Give your companion a new name:</p>
            <input type="text" class="form-input" id="rename-input" placeholder="Enter new name" maxlength="50">
        </div>
        <div class="modal-actions">
            <button class="modal-btn-cancel" onclick="closeRenameModal()">Cancel</button>
            <button class="modal-btn-confirm" onclick="confirmRename()">
                <i class="fas fa-save"></i>
                Save
            </button>
        </div>
    </div>
</div>

<!-- Item Use Modal -->
<div class="modal" id="item-modal">
    <div class="modal-backdrop" onclick="closeItemModal()"></div>
    <div class="modal-content modal-fullscreen-mobile">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-hand-holding-heart" id="item-modal-icon-header"></i>
                <span id="item-modal-title">Use Item</span>
            </h3>
            <button class="modal-close" onclick="closeItemModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="item-list premium-list" id="item-list">
                <!-- Items rendered by JS -->
            </div>
        </div>
    </div>
</div>

<?php // Shop Buy Modal ?>
<div class="modal-overlay" id="shop-modal">
    <div class="modal-content modal-bottom-sheet">
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
    <div class="modal-content" style="max-width: 560px; max-height: 85vh;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-book-open"></i> Help & Tutorial</h3>
            <button class="modal-close" onclick="closeHelpModal()">&times;</button>
        </div>
        <div class="modal-body" style="overflow-y: auto; padding: 16px;">

            <!-- Getting Started -->
            <div class="help-section" style="margin-bottom: 20px;">
                <h4 style="color: var(--gold); margin-bottom: 8px;"><i class="fas fa-paw"></i> Getting Started</h4>
                <p style="color: #aaa; line-height: 1.6; font-size: 0.9rem;">
                    Welcome to <strong style="color: #fff;">Mediterranean of Egypt</strong>! Start by rolling for your
                    first pet in the <strong style="color: var(--gold);">Gacha</strong> tab using Gold. Each roll costs
                    gold and gives you a random pet with different elements and rarities.
                </p>
            </div>

            <!-- Pet Care -->
            <div class="help-section" style="margin-bottom: 20px;">
                <h4 style="color: var(--gold); margin-bottom: 8px;"><i class="fas fa-heart"></i> Pet Care & Stats</h4>
                <p style="color: #aaa; line-height: 1.6; font-size: 0.9rem; margin-bottom: 8px;">
                    Your pet has 3 vital stats that decay over time:
                </p>
                <div style="background: rgba(255,255,255,0.05); border-radius: 8px; padding: 10px; margin-bottom: 8px;">
                    <div style="color: #ccc; font-size: 0.85rem; line-height: 1.8;">
                        🍗 <strong>Hunger</strong> — decreases <strong style="color: #E74C3C;">-4/hour</strong>. Use
                        <em>Food</em> items to restore.<br>
                        😊 <strong>Mood</strong> — decreases <strong style="color: #E74C3C;">-3/hour</strong>. Play
                        <em>Rhythm Game</em> to restore.<br>
                        ❤️ <strong>HP</strong> — takes damage when hunger or mood reach 0.
                    </div>
                </div>
                <p style="color: #E74C3C; font-size: 0.85rem; line-height: 1.5;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> When hunger=0, pet loses
                    <strong>-10 HP/hour</strong>. When mood=0, loses <strong>-5 HP/hour</strong>. If HP reaches 0, your
                    pet <strong>dies</strong>! Use a Revival item to bring it back.
                </p>
            </div>

            <!-- Shelter -->
            <div class="help-section" style="margin-bottom: 20px;">
                <h4 style="color: var(--gold); margin-bottom: 8px;"><i class="fas fa-home"></i> Shelter</h4>
                <p style="color: #aaa; line-height: 1.6; font-size: 0.9rem;">
                    Send pets to the <strong style="color: #fff;">Shelter</strong> to <strong>pause stat decay</strong>.
                    Sheltered pets won't lose hunger, mood, or HP — but they also can't be used in battle. Retrieve them
                    anytime to make them active again.
                </p>
            </div>

            <!-- Battle System -->
            <div class="help-section" style="margin-bottom: 20px;">
                <h4 style="color: var(--gold); margin-bottom: 8px;"><i class="fas fa-shield-alt"></i> Battle System</h4>
                <p style="color: #aaa; line-height: 1.6; font-size: 0.9rem; margin-bottom: 8px;">
                    Enter the <strong style="color: #fff;">Arena</strong> to battle other players' pets or Wild
                    Trainers.
                </p>
                <div style="background: rgba(255,255,255,0.05); border-radius: 8px; padding: 10px; margin-bottom: 8px;">
                    <div style="color: #ccc; font-size: 0.85rem; line-height: 1.8;">
                        ⚔️ Choose skills each turn — damage depends on Attack, Defense, and Element.<br>
                        🎯 <strong>Critical Hits</strong> — 10% chance to deal <strong>1.5x damage</strong>.<br>
                        🏆 <strong>Win</strong> → earn Gold + EXP + Rank Points.<br>
                        💀 <strong>Lose</strong> → your pet loses <strong style="color: #E74C3C;">-10 HP</strong>. Can
                        die!
                    </div>
                </div>
                <p style="color: #aaa; font-size: 0.85rem; margin-bottom: 6px;"><strong style="color: #fff;">Element
                        Advantages (2x damage):</strong></p>
                <div style="background: rgba(255,255,255,0.05); border-radius: 8px; padding: 10px;">
                    <div style="color: #ccc; font-size: 0.85rem; line-height: 1.8; text-align: center;">
                        🔥 Fire → 💨 Air → 🌍 Earth → 💧 Water → 🔥 Fire<br>
                        ☀️ Light ↔ 🌙 Dark <span style="color: #888;">(both super effective)</span>
                    </div>
                </div>
            </div>

            <!-- Evolution -->
            <div class="help-section" style="margin-bottom: 20px;">
                <h4 style="color: var(--gold); margin-bottom: 8px;"><i class="fas fa-dna"></i> Evolution</h4>
                <p style="color: #aaa; line-height: 1.6; font-size: 0.9rem; margin-bottom: 8px;">
                    Pets evolve through 3 stages. Each stage has a <strong style="color: #fff;">level cap</strong> —
                    evolve to unlock higher levels!
                </p>
                <div style="background: rgba(255,255,255,0.05); border-radius: 8px; padding: 10px;">
                    <div style="color: #ccc; font-size: 0.85rem; line-height: 1.8;">
                        🥚 <strong>Egg</strong> → max Lv.30 → evolve to Baby<br>
                        🐣 <strong>Baby</strong> → max Lv.70 → evolve to Adult<br>
                        👑 <strong>Adult</strong> → max Lv.99 (final form)
                    </div>
                </div>
            </div>

            <!-- Shop Items -->
            <div class="help-section" style="margin-bottom: 20px;">
                <h4 style="color: var(--gold); margin-bottom: 8px;"><i class="fas fa-store"></i> Shop Items</h4>
                <div style="background: rgba(255,255,255,0.05); border-radius: 8px; padding: 10px;">
                    <div style="color: #ccc; font-size: 0.85rem; line-height: 1.8;">
                        🍗 <strong>Food</strong> — restores Hunger (20/50/100).<br>
                        💊 <strong>Potions</strong> — restores HP (+30/+60/Full).<br>
                        💀 <strong>Revival</strong> — revives dead pets (50% or full stats).<br>
                        📜 <strong>EXP Scrolls</strong> — gives instant EXP (200/500).<br>
                        🛡️ <strong>Divine Shield</strong> — blocks 1 attack in battle.<br>
                        🎟️ <strong>Arena Ticket</strong> — resets your daily battle quota.
                    </div>
                </div>
            </div>

            <!-- Rhythm Game -->
            <div class="help-section" style="margin-bottom: 20px;">
                <h4 style="color: var(--gold); margin-bottom: 8px;"><i class="fas fa-music"></i> Rhythm Game</h4>
                <p style="color: #aaa; line-height: 1.6; font-size: 0.9rem;">
                    Play the Rhythm Game to boost your pet's <strong style="color: #fff;">Mood</strong>! Tap to the beat
                    and score high — the better your score, the more mood your pet gains. Access it via the <strong
                        style="color: var(--gold);">Play</strong> button on the My Pet tab.
                </p>
            </div>

            <!-- Tips -->
            <div class="help-section" style="margin-bottom: 8px;">
                <h4 style="color: var(--gold); margin-bottom: 8px;"><i class="fas fa-lightbulb"></i> Pro Tips</h4>
                <div
                    style="background: rgba(255, 193, 7, 0.08); border: 1px solid rgba(255, 193, 7, 0.2); border-radius: 8px; padding: 10px;">
                    <div style="color: #ccc; font-size: 0.85rem; line-height: 1.8;">
                        💡 Shelter pets you're not using to prevent stat decay.<br>
                        💡 Use element advantage in battle for 2x damage!<br>
                        💡 Win 3+ battles in a row for streak bonuses.<br>
                        💡 Keep hunger & mood above 0 to prevent HP loss.<br>
                        💡 Shiny pets are rare — they look visually unique!
                    </div>
                </div>
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