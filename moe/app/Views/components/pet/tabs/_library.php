<!-- BESTIARY / LIBRARY TAB -->
<section id="library" class="tab-panel">
    <div class="library-section">
        <!-- Discovery Progress Header -->
        <div class="library-header glass-premium">
            <div class="discovery-header-top">
                <div class="discovery-info">
                    <h3><i class="fas fa-book-spells"></i> Bestiary Collection</h3>
                    <p class="discovery-subtitle">Documenting the mystical creatures of MOE Academy</p>
                </div>
                <div class="discovery-stats-badge">
                    <span class="stat-value"><?= $discoveryStats['discovered'] ?></span>
                    <span class="stat-label">/ <?= $discoveryStats['total'] ?> Found</span>
                </div>
            </div>

            <div class="discovery-progress-wrapper">
                <div class="discovery-progress-container">
                    <div class="discovery-progress-bar pulse-glow" style="width: <?= $discoveryStats['percentage'] ?>%">
                        <div class="progress-shine"></div>
                    </div>
                </div>
                <span class="percentage-label"><?= $discoveryStats['percentage'] ?>% COMPLETE</span>
            </div>
        </div>

        <?php
        // Group species by rarity
        $groupedSpecies = [
            'Mythical' => [],
            'Legendary' => [],
            'Epic' => [],
            'Rare' => [],
            'Common' => []
        ];
        foreach ($allSpecies as $s) {
            $groupedSpecies[$s['rarity']][] = $s;
        }
        ?>

        <?php foreach ($groupedSpecies as $rarity => $speciesList): ?>
            <?php if (empty($speciesList))
                continue; ?>

            <div class="rarity-group-section">
                <h2 class="rarity-group-title <?= strtolower($rarity) ?>">
                    <span><?= $rarity ?> Grade</span>
                    <div class="title-line"></div>
                </h2>

                <div class="library-grid">
                    <?php foreach ($speciesList as $species): ?>
                        <?php
                        $isDiscovered = in_array($species['id'], $discoveredIds);
                        $isShinyDiscovered = in_array($species['id'], $shinyDiscoveredIds);
                        $rarityClass = strtolower($species['rarity']);
                        $elementClass = strtolower($species['element']);
                        ?>
                        <div
                            class="library-card glass-premium <?= $isDiscovered ? 'discovered' : 'undiscovered' ?> <?= $rarityClass ?>-border">
                            <div class="library-img-container">
                                <?php if ($isDiscovered): ?>
                                    <?php if ($isShinyDiscovered): ?>
                                        <div class="shiny-badge-premium discovery-badge">
                                            <i class="fas fa-star"></i> SHINY
                                        </div>
                                    <?php endif; ?>
                                    <div class="element-icon-bg <?= $elementClass ?>">
                                        <i
                                            class="fas fa-<?= $elementClass === 'fire' ? 'fire' : ($elementClass === 'water' ? 'tint' : ($elementClass === 'earth' ? 'leaf' : ($elementClass === 'air' ? 'wind' : ($elementClass === 'dark' ? 'moon' : 'sun')))) ?>"></i>
                                    </div>
                                    <img src="<?= base_url('assets/pets/' . $species['img_adult']) ?>" alt="<?= $species['name'] ?>"
                                        class="pet-img <?= $isShinyDiscovered ? 'shiny-glow' : '' ?>">
                                <?php else: ?>
                                    <div class="mystery-mark">?</div>
                                    <img src="<?= base_url('assets/pets/' . $species['img_adult']) ?>" alt="???"
                                        class="pet-img pet-silhouette">
                                <?php endif; ?>
                            </div>

                            <div class="library-info">
                                <h4 class="species-name"><?= $isDiscovered ? $species['name'] : '???' ?></h4>
                                <div class="species-meta">
                                    <span class="rarity-tag <?= $rarityClass ?>"><?= $rarity ?></span>
                                    <?php if ($isDiscovered): ?>
                                        <span class="element-tag <?= $elementClass ?>"><?= $species['element'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="species-description-box">
                                <?php if ($isDiscovered): ?>
                                    <p class="species-description"><?= $species['description'] ?? 'A rare mystical creature.' ?></p>
                                <?php else: ?>
                                    <p class="species-description locked-text">
                                        <i class="fas fa-lock"></i> Obtain this creature to reveal its mystical lore...
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<style>
    /* Library Header Improvements */
    .library-header {
        background: linear-gradient(135deg, rgba(20, 20, 30, 0.8), rgba(40, 40, 60, 0.5));
        border: 1px solid rgba(184, 134, 11, 0.3);
        padding: 30px;
        margin-bottom: 40px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }

    .discovery-header-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
    }

    .library-header h3 {
        font-family: 'Cinzel', serif;
        font-size: 1.8rem;
        color: var(--gold);
        text-shadow: 0 0 15px rgba(184, 134, 11, 0.5);
        margin: 0;
    }

    .discovery-subtitle {
        color: var(--text-dim);
        font-size: 0.9rem;
        margin: 5px 0 0;
    }

    .discovery-stats-badge {
        background: rgba(184, 134, 11, 0.15);
        border: 1px solid var(--gold);
        padding: 8px 15px;
        border-radius: 12px;
        text-align: center;
    }

    .stat-value {
        display: block;
        font-size: 1.4rem;
        font-weight: 800;
        color: var(--gold);
        line-height: 1;
    }

    .stat-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        color: var(--text-dim);
        letter-spacing: 1px;
    }

    .discovery-progress-wrapper {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .discovery-progress-container {
        flex-grow: 1;
        height: 14px;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .discovery-progress-bar {
        height: 100%;
        position: relative;
        background: linear-gradient(90deg, #b8860b, #ffd700);
    }

    .percentage-label {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--gold);
        min-width: 100px;
    }

    /* Rarity Sections */
    .rarity-group-section {
        margin-bottom: 50px;
    }

    .rarity-group-title {
        font-family: 'Cinzel', serif;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
        padding-left: 10px;
    }

    .rarity-group-title .title-line {
        flex-grow: 1;
        height: 1px;
        background: linear-gradient(90deg, currentColor, transparent);
        opacity: 0.3;
    }

    .rarity-group-title.mythical {
        color: #00ffff;
        text-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
    }

    .rarity-group-title.legendary {
        color: #ffae00;
        text-shadow: 0 0 10px rgba(255, 174, 0, 0.5);
    }

    .rarity-group-title.epic {
        color: #bf00ff;
        text-shadow: 0 0 10px rgba(191, 0, 255, 0.5);
    }

    .rarity-group-title.rare {
        color: #0080ff;
        text-shadow: 0 0 10px rgba(0, 128, 255, 0.5);
    }

    .rarity-group-title.common {
        color: #ffffff;
        text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
    }

    /* Card Enhancements */
    .library-card {
        position: relative;
        overflow: hidden;
        padding: 15px !important;
        display: flex;
        flex-direction: column;
        border-width: 1px !important;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .library-card.discovered {
        background: rgba(255, 255, 255, 0.05);
    }

    .library-card.undiscovered {
        background: rgba(0, 0, 0, 0.4);
    }

    .library-card.mythical-border {
        border-color: rgba(0, 255, 255, 0.3) !important;
    }

    .library-card.legendary-border {
        border-color: rgba(255, 174, 0, 0.3) !important;
    }

    .library-card.epic-border {
        border-color: rgba(191, 0, 255, 0.3) !important;
    }

    .library-card.rare-border {
        border-color: rgba(0, 128, 255, 0.3) !important;
    }

    .library-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        background: rgba(255, 255, 255, 0.08);
    }

    .library-img-container {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 12px;
        padding: 10px;
        margin-bottom: 12px;
        position: relative;
    }

    .discovery-badge.shiny-badge {
        /* Styles moved to gacha_premium.css */
    }

    .element-icon-bg {
        position: absolute;
        bottom: 5px;
        left: 5px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        color: #fff;
        opacity: 0.7;
    }

    .mystery-mark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 3rem;
        font-weight: 900;
        color: rgba(255, 255, 255, 0.05);
        z-index: 1;
    }

    .species-name {
        font-family: 'Cinzel', serif;
        font-size: 1.1rem;
        margin: 0 0 8px;
        color: #fff;
    }

    .species-meta {
        display: flex;
        gap: 8px;
        margin-bottom: 12px;
    }

    .rarity-tag {
        font-size: 0.65rem;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .rarity-tag.mythical {
        background: rgba(0, 255, 255, 0.2);
        color: #00ffff;
        border: 1px solid rgba(0, 255, 255, 0.4);
    }

    .rarity-tag.legendary {
        background: rgba(255, 174, 0, 0.2);
        color: #ffae00;
        border: 1px solid rgba(255, 174, 0, 0.4);
    }

    .rarity-tag.epic {
        background: rgba(191, 0, 255, 0.2);
        color: #bf00ff;
        border: 1px solid rgba(191, 0, 255, 0.4);
    }

    .rarity-tag.rare {
        background: rgba(0, 128, 255, 0.2);
        color: #0080ff;
        border: 1px solid rgba(0, 128, 255, 0.4);
    }

    .rarity-tag.common {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .element-tag {
        font-size: 0.65rem;
        padding: 2px 8px;
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-dim);
    }

    .species-description-box {
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        padding-top: 10px;
        flex-grow: 1;
    }

    .species-description {
        font-size: 0.75rem;
        line-height: 1.5;
        color: var(--text-dim);
        margin: 0;
    }

    .locked-text {
        font-style: italic;
        opacity: 0.5;
    }

    .pet-silhouette {
        filter: brightness(0) blur(8px);
        opacity: 0.4;
    }

    .element-icon-bg.fire {
        background: #ff4500;
    }

    .element-icon-bg.water {
        background: #0080ff;
    }

    .element-icon-bg.earth {
        background: #8b4513;
    }

    .element-icon-bg.air {
        background: #a9a9a9;
    }

    .element-icon-bg.dark {
        background: #4b0082;
    }

    .element-icon-bg.light {
        background: #ffd700;
    }
</style>