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