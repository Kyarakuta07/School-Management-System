<header class="guild-header">
    <picture>
        <source media="(max-width: 768px)"
            srcset="<?= base_url('assets/sanctuhall/hallmobile_' . esc($factionSlug) . '.jpeg') ?>">
        <img src="<?= base_url('assets/sanctuhall/halldesktop_' . esc($factionSlug) . '.jpeg') ?>" alt="Background"
            class="guild-bg" onerror="this.src='<?= asset_v('assets/map/map_moe_cleanup.jpeg') ?>'">
    </picture>
    <div class="guild-title-wrapper">
        <img src="<?= base_url('assets/faction emblem/faction_' . esc($factionSlug) . '.png') ?>" alt="Emblem"
            class="guild-emblem">
        <h1 class="guild-name">
            <?= esc($sanctuary['nama_sanctuary']) ?>
        </h1>
        <div class="guild-stats">
            <div class="stat-item"><i class="fas fa-users"></i>
                <?= number_format($memberCount) ?> Scholars
            </div>
            <div class="stat-item"><i class="fas fa-star"></i>
                <?= number_format($totalPp ?? 0) ?> Total PP
            </div>
        </div>
    </div>
</header>