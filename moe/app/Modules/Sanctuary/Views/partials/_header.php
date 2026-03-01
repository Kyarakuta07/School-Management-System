<?php
/**
 * sanctuary/_header.php
 * Displays the sanctuary emblem, name, role, and membership/PP stats.
 */
?>
<header class="sanctuary-header">
    <img src="<?= base_url('assets/faction emblem/faction_' . esc($factionSlug) . '.png') ?>" alt="Emblem"
        class="header-emblem" onerror="this.style.display='none'">
    <div class="header-info">
        <h1 class="header-title">
            <?= esc($sanctuaryName) ?> Sanctuary
        </h1>
        <p class="header-subtitle"><i class="fas fa-shield-alt"></i> Your role: <strong style="color: var(--gold);">
                <?= esc(ucfirst($userRole)) ?>
            </strong></p>
    </div>
    <div class="header-stats">
        <div class="stat-box"><span class="stat-value">
                <?= number_format($memberCount) ?>
            </span><span class="stat-label">Members</span></div>
        <div class="stat-box"><span class="stat-value">
                <?= number_format($totalPp ?? 0) ?>
            </span><span class="stat-label">Total PP</span></div>
    </div>
</header>