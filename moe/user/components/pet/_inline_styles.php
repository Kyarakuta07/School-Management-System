<style>
    /* Additional inline styles for modals and forms */
    .form-input {
        width: 100%;
        padding: 14px 16px;
        background: rgba(0, 0, 0, 0.4);
        border: 1px solid var(--border-gold);
        border-radius: var(--radius-md);
        color: #fff;
        font-family: inherit;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--gold);
        background: rgba(0, 0, 0, 0.6);
        box-shadow: 0 0 20px rgba(218, 165, 32, 0.2);
    }

    .shop-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
        overflow-x: auto;
        padding-bottom: 8px;
    }

    .shop-tab {
        padding: 10px 20px;
        background: transparent;
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-full);
        color: #666;
        font-family: inherit;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .shop-tab.active {
        background: rgba(218, 165, 32, 0.15);
        border-color: var(--gold);
        color: var(--gold);
    }

    .shop-grid,
    .inventory-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 24px;
    }

    .shop-item,
    .inventory-item {
        background: var(--bg-card);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-md);
        padding: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .shop-item:hover,
    .inventory-item:hover {
        border-color: var(--border-gold);
        transform: translateY(-2px);
    }

    .shop-item img,
    .inventory-item img {
        width: 50px;
        height: 50px;
        object-fit: contain;
        margin-bottom: 8px;
    }

    .shop-item-name,
    .inventory-item-name {
        font-size: 0.75rem;
        color: #ccc;
        margin-bottom: 4px;
    }

    .shop-item-price {
        font-size: 0.7rem;
        color: var(--gold);
        font-weight: 700;
    }

    .inventory-item-qty {
        font-size: 0.65rem;
        color: #888;
    }

    .inventory-section {
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid var(--border-subtle);
    }

    .arena-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .arena-battles {
        padding: 6px 14px;
        background: rgba(218, 165, 32, 0.1);
        border: 1px solid var(--border-gold);
        border-radius: var(--radius-full);
        font-size: 0.75rem;
        color: var(--gold);
    }

    .opponents-grid {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .opponent-card {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px;
        background: var(--bg-card);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .opponent-card:hover {
        border-color: var(--border-gold);
        transform: translateX(4px);
    }

    .opponent-img {
        width: 60px;
        height: 60px;
        object-fit: contain;
    }

    .opponent-info {
        flex: 1;
    }

    .opponent-name {
        font-weight: 700;
        color: #fff;
        margin-bottom: 4px;
    }

    .opponent-level {
        font-size: 0.8rem;
        color: var(--gold);
    }

    .achievements-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }

    .achievement-card {
        background: var(--bg-card);
        border: 1px solid var(--border-subtle);
        border-radius: var(--radius-md);
        padding: 16px;
        text-align: center;
    }

    .achievement-card.unlocked {
        border-color: var(--gold);
    }

    .achievement-card.locked {
        opacity: 0.5;
        filter: grayscale(50%);
    }

    .achievement-icon {
        font-size: 2rem;
        margin-bottom: 8px;
    }

    .achievement-name {
        font-size: 0.75rem;
        color: #ccc;
    }

    .item-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .item-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: rgba(0, 0, 0, 0.3);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .item-row:hover {
        background: rgba(218, 165, 32, 0.1);
    }

    .item-row img {
        width: 40px;
        height: 40px;
        object-fit: contain;
    }

    .item-info {
        flex: 1;
    }

    .item-name {
        font-weight: 600;
        color: #fff;
    }

    .item-qty {
        font-size: 0.8rem;
        color: #888;
    }

    /* ===================================================
       MODAL ICON WRAPPERS - Responsive & Secure
       =================================================== */

    .modal-icon-wrapper {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        background: linear-gradient(135deg, #2a2a2a 0%, #1a1a1a 100%);
        border: 3px solid #d4af37;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
    }

    .modal-icon-wrapper i {
        font-size: 48px;
        color: #d4af37;
        z-index: 1;
    }

    /* Small variant for bulk use modal */
    .modal-icon-wrapper.small {
        width: 70px;
        height: 70px;
        border-width: 2px;
    }

    .modal-icon-wrapper.small i {
        font-size: 32px;
    }

    /* Rarity color variants (XSS-safe: classes set by controlled code) */
    .modal-icon-wrapper.common {
        border-color: #9ca3af;
        background: linear-gradient(135deg, #3a3a3a 0%, #2a2a2a 100%);
    }

    .modal-icon-wrapper.common i {
        color: #9ca3af;
    }

    .modal-icon-wrapper.uncommon {
        border-color: #10b981;
        background: linear-gradient(135deg, #1a3a2a 0%, #0a2a1a 100%);
    }

    .modal-icon-wrapper.uncommon i {
        color: #10b981;
    }

    .modal-icon-wrapper.rare {
        border-color: #3b82f6;
        background: linear-gradient(135deg, #1a2a4a 0%, #0a1a3a 100%);
    }

    .modal-icon-wrapper.rare i {
        color: #3b82f6;
    }

    .modal-icon-wrapper.epic {
        border-color: #a855f7;
        background: linear-gradient(135deg, #2a1a4a 0%, #1a0a3a 100%);
    }

    .modal-icon-wrapper.epic i {
        color: #a855f7;
    }

    .modal-icon-wrapper.legendary {
        border-color: #f59e0b;
        background: linear-gradient(135deg, #4a3a1a 0%, #3a2a0a 100%);
        animation: legendaryPulse 2s ease-in-out infinite;
    }

    .modal-icon-wrapper.legendary i {
        color: #f59e0b;
        animation: legendaryGlow 2s ease-in-out infinite;
    }

    @keyframes legendaryPulse {

        0%,
        100% {
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        50% {
            box-shadow: 0 4px 30px rgba(245, 158, 11, 0.6);
        }
    }

    @keyframes legendaryGlow {

        0%,
        100% {
            text-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
        }

        50% {
            text-shadow: 0 0 20px rgba(245, 158, 11, 0.8);
        }
    }

    /* Responsive breakpoints for all screen sizes */
    @media (max-width: 768px) {

        /* Tablet */
        .modal-icon-wrapper {
            width: 80px;
            height: 80px;
        }

        .modal-icon-wrapper i {
            font-size: 40px;
        }

        .modal-icon-wrapper.small {
            width: 60px;
            height: 60px;
        }

        .modal-icon-wrapper.small i {
            font-size: 28px;
        }
    }

    @media (max-width: 480px) {

        /* Mobile */
        .modal-icon-wrapper {
            width: 70px;
            height: 70px;
        }

        .modal-icon-wrapper i {
            font-size: 32px;
        }

        .modal-icon-wrapper.small {
            width: 50px;
            height: 50px;
        }

        .modal-icon-wrapper.small i {
            font-size: 24px;
        }
    }

    @media (min-width: 1920px) {

        /* Large desktop (Full HD+) */
        .modal-icon-wrapper {
            width: 120px;
            height: 120px;
        }

        .modal-icon-wrapper i {
            font-size: 56px;
        }

        .modal-icon-wrapper.small {
            width: 90px;
            height: 90px;
        }

        .modal-icon-wrapper.small i {
            font-size: 40px;
        }
    }

    @media (min-width: 2560px) {

        /* 4K displays */
        .modal-icon-wrapper {
            width: 140px;
            height: 140px;
        }

        .modal-icon-wrapper i {
            font-size: 64px;
        }
    }

    @media (min-width: 480px) {

        .shop-grid,
        .inventory-grid {
            grid-template-columns: repeat(4, 1fr);
        }

        .achievements-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }
</style>