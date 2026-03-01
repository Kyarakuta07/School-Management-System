<!-- Evolution Modal -->
<div class="modal-overlay" id="evolution-modal">
    <div class="modal-content" style="max-width: 500px; max-height: 80vh;">
        <div class="modal-header">
            <h3 class="modal-title" id="evo-title">Evolve Pet</h3>
            <button class="modal-close" onclick="closeEvolutionModal()">&times;</button>
        </div>
        <div class="modal-body" style="overflow-y: auto;">
            <div
                style="text-align: center; margin-bottom: 16px; padding: 16px; background: rgba(155, 89, 182, 0.1); border-radius: 12px;">
                <p style="color: #888; font-size: 0.85rem;">Current Stage: <span id="evo-current-stage"
                        style="color: var(--gold);">Egg</span></p>
                <p style="color: #888; font-size: 0.85rem;">Next Stage: <span id="evo-next-stage"
                        style="color: #9B59B6;">Baby</span></p>
                <p style="color: #888; font-size: 0.85rem;">Required Level: <span id="evo-required-level"
                        style="color: var(--gold);">10</span></p>
                <p style="color: #888; font-size: 0.85rem;">Required Rarity: <span id="evo-required-rarity"
                        style="color: var(--gold);">-</span></p>
            </div>
            <div style="margin-bottom: 12px;">
                <p style="color: #fff; font-weight: 600; margin-bottom: 8px;">Select 3 Fodder Pets (<span
                        id="evo-selected-count">0</span>/3)</p>
                <p style="color: #666; font-size: 0.8rem;">These pets will be consumed in the evolution.</p>
            </div>
            <div class="fodder-grid" id="fodder-grid"
                style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; max-height: 200px; overflow-y: auto;">
                <!-- Fodder cards rendered by JS -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeEvolutionModal()">Cancel</button>
            <button class="btn-primary" id="confirm-evolution-btn" onclick="confirmEvolution()" disabled
                style="background: linear-gradient(135deg, #9B59B6, #8E44AD);">
                <i class="fas fa-star"></i> Evolve
            </button>
        </div>
    </div>
</div>

<!-- Evolution Confirm Modal -->
<div class="modal-overlay" id="evolution-confirm-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Evolution</h3>
            <button class="modal-close" onclick="closeEvoConfirmModal()">&times;</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #F39C12; margin-bottom: 16px;"></i>
            <p style="color: #fff; margin-bottom: 8px;">Are you sure you want to evolve?</p>
            <p style="color: #E74C3C; font-size: 0.85rem;">The 3 selected fodder pets will be permanently
                consumed!</p>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeEvoConfirmModal()">Cancel</button>
            <button class="btn-primary" id="proceed-evolution-btn" onclick="proceedEvolution()"
                style="background: linear-gradient(135deg, #9B59B6, #8E44AD);">
                <i class="fas fa-star"></i> Proceed with Evolution
            </button>
        </div>
    </div>
</div>