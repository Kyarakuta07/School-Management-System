<!-- Custom Confirm Modal -->
<div id="confirm-modal" class="confirm-modal-overlay" style="display: none;">
    <div class="confirm-modal-content">
        <div class="confirm-modal-icon">
            <i class="fas fa-question-circle"></i>
        </div>
        <h3 class="confirm-modal-title" id="confirm-modal-title">Confirm Action</h3>
        <p class="confirm-modal-message" id="confirm-modal-message">Are you sure?</p>
        <div class="confirm-modal-buttons">
            <button class="confirm-modal-btn confirm-btn-cancel" id="confirm-modal-cancel">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="confirm-modal-btn confirm-btn-ok" id="confirm-modal-ok">
                <i class="fas fa-check"></i> OK
            </button>
        </div>
    </div>
</div>

<style>
    /* Custom Confirm Modal Styles */
    .confirm-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(4px);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        animation: fadeIn 0.2s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .confirm-modal-content {
        background: linear-gradient(145deg, #1a1a2e, #16213e);
        border: 1px solid rgba(218, 165, 32, 0.3);
        border-radius: 16px;
        padding: 2rem;
        max-width: 340px;
        width: 90%;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5),
            0 0 40px rgba(218, 165, 32, 0.1);
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .confirm-modal-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 1rem;
        background: rgba(218, 165, 32, 0.15);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .confirm-modal-icon i {
        font-size: 1.8rem;
        color: #DAA520;
    }

    .confirm-modal-title {
        color: #fff;
        font-family: 'Cinzel', serif;
        font-size: 1.2rem;
        margin: 0 0 0.5rem 0;
        letter-spacing: 1px;
    }

    .confirm-modal-message {
        color: #aaa;
        font-size: 0.95rem;
        margin: 0 0 1.5rem 0;
        line-height: 1.5;
    }

    .confirm-modal-buttons {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
    }

    .confirm-modal-btn {
        flex: 1;
        padding: 0.75rem 1rem;
        border: none;
        border-radius: 8px;
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
    }

    .confirm-btn-cancel {
        background: rgba(255, 255, 255, 0.1);
        color: #888;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .confirm-btn-cancel:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
    }

    .confirm-btn-ok {
        background: linear-gradient(135deg, #DAA520, #B8860B);
        color: #000;
        box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3);
    }

    .confirm-btn-ok:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(218, 165, 32, 0.4);
    }

    .confirm-btn-ok:active {
        transform: translateY(0);
    }
</style>

<script>
    /**
     * Custom Confirm Modal
     * Replaces browser's native confirm() with styled modal
     * 
     * Usage:
     *   showConfirm('Retrieve Shadowfox?', 'Retrieve from Shelter').then(confirmed => {
     *       if (confirmed) { ... }
     *   });
     */
    function showConfirm(message, title = 'Confirm Action', icon = 'fa-question-circle') {
        return new Promise((resolve) => {
            const modal = document.getElementById('confirm-modal');
            const titleEl = document.getElementById('confirm-modal-title');
            const messageEl = document.getElementById('confirm-modal-message');
            const okBtn = document.getElementById('confirm-modal-ok');
            const cancelBtn = document.getElementById('confirm-modal-cancel');
            const iconEl = modal.querySelector('.confirm-modal-icon i');

            // Set content
            titleEl.textContent = title;
            messageEl.textContent = message;
            iconEl.className = `fas ${icon}`;

            // Show modal
            modal.style.display = 'flex';

            // Cleanup function
            const cleanup = (result) => {
                modal.style.display = 'none';
                okBtn.removeEventListener('click', onOk);
                cancelBtn.removeEventListener('click', onCancel);
                modal.removeEventListener('click', onOverlayClick);
                resolve(result);
            };

            // Event handlers
            const onOk = () => cleanup(true);
            const onCancel = () => cleanup(false);
            const onOverlayClick = (e) => {
                if (e.target === modal) cleanup(false);
            };

            // Attach events
            okBtn.addEventListener('click', onOk);
            cancelBtn.addEventListener('click', onCancel);
            modal.addEventListener('click', onOverlayClick);

            // Focus OK button for keyboard accessibility
            setTimeout(() => okBtn.focus(), 100);
        });
    }
</script>