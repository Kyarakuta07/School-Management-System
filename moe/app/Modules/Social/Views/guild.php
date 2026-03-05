<?= $this->extend('layouts/user') ?>

<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= asset_v('css/auth/landing-style.css') ?>">
<link rel="stylesheet" href="<?= asset_v('css/social/guild_style.css') ?>">
<style>
    /* Override global body flex centering that breaks multi-section guild page */
    body.page-guild {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        justify-content: flex-start !important;
        padding: 0 !important;
        min-height: 100vh;
    }
    /* Ensure sections take full width */
    body.page-guild .guild-header,
    body.page-guild .guild-section,
    body.page-guild > div:last-child {
        width: 100%;
    }
    /* Hide generic background for guild (has its own bg) */
    body.page-guild .bg-fixed,
    body.page-guild .bg-overlay {
        display: none;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- NAVBAR -->
<?= $this->include('App\Modules\User\Views\partials\navbar') ?>

<!-- HEADER -->
<header class="guild-header">
    <picture>
        <source media="(max-width: 768px)"
            srcset="<?= base_url('assets/sanctuhall/hallmobile_' . esc($factionSlug, 'url') . '.jpeg') ?>">
        <img src="<?= base_url('assets/sanctuhall/halldesktop_' . esc($factionSlug, 'url') . '.jpeg') ?>"
            alt="Background" class="guild-bg" onerror="this.src='<?= asset_v('assets/map/map_moe_cleanup.jpeg') ?>'">
    </picture>

    <div class="guild-title-wrapper">
        <img src="<?= base_url('assets/faction emblem/faction_' . esc($factionSlug, 'url') . '.png') ?>" alt="Emblem"
            class="guild-emblem">
        <h1 class="guild-name">
            <?= esc($sanctuary['nama_sanctuary']) ?>
        </h1>

        <div class="guild-stats">
            <div class="stat-item"><i class="fas fa-users"></i>
                <?= number_format($memberCount) ?> Scholars
            </div>
            <div class="stat-item"><i class="fas fa-star"></i>
                <?= number_format($totalPP) ?> Total PP
            </div>
        </div>

        <?php if (!$isMember): ?>
            <div style="margin-top: 1rem; color: #aaa; font-style: italic;">(Visitor View)</div>
        <?php endif; ?>
    </div>
</header>

<!-- THRONE ROOM -->
<section class="guild-section">
    <div class="section-container">
        <h2 class="section-title">The Throne Room</h2>
        <div class="throne-room">
            <!-- HOSA (Leader) -->
            <div class="leader-card hosa-card">
                <div class="role-badge">HOSA</div>
                <div class="leader-avatar-wrapper">
                    <?php if (!empty($hosa['profile_photo'])): ?>
                        <img src="<?= base_url('assets/uploads/profiles/' . esc(basename($hosa['profile_photo']))) ?>"
                            alt="Hosa" class="leader-avatar">
                    <?php else: ?>
                        <div class="leader-avatar leader-avatar-fallback">
                            <i class="fas fa-crown"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($hosa): ?>
                    <div class="leader-name">
                        <?= esc($hosa['nama_lengkap']) ?>
                    </div>
                    <div class="leader-username">@
                        <?= esc($hosa['username']) ?>
                    </div>
                <?php else: ?>
                    <div class="leader-name">Vacant</div>
                    <div class="leader-username">No leader assigned</div>
                <?php endif; ?>
            </div>

            <!-- VIZIER (Vice) -->
            <?php if (!empty($viziers)): ?>
                <?php foreach ($viziers as $vizier): ?>
                    <div class="leader-card">
                        <div class="role-badge" style="background: silver;">VIZIER</div>
                        <div class="leader-avatar-wrapper">
                            <?php if (!empty($vizier['profile_photo'])): ?>
                                <img src="<?= base_url('assets/uploads/profiles/' . esc(basename($vizier['profile_photo']))) ?>"
                                    alt="Vizier" class="leader-avatar">
                            <?php else: ?>
                                <div class="leader-avatar leader-avatar-fallback">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="leader-name">
                            <?= esc($vizier['nama_lengkap']) ?>
                        </div>
                        <div class="leader-username">@
                            <?= esc($vizier['username']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="leader-card">
                    <div class="role-badge" style="background: silver;">VIZIER</div>
                    <div class="leader-avatar leader-avatar-fallback">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="leader-name">Vacant</div>
                    <div class="leader-username">No vizier assigned</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- MEMBER-ONLY ACCESS BANNER -->
<?php if ($isMember): ?>
    <section class="guild-section" style="text-align: center; padding: 2rem;">
        <div class="section-container">
            <a href="<?= base_url('my-sanctuary') ?>" class="back-link" style="font-size: 1rem;">
                <i class="fas fa-door-open"></i> Enter Control Room (Treasury, Upgrades & More)
            </a>
        </div>
    </section>
<?php endif; ?>

<!-- BARRACKS (Member List) -->
<section class="guild-section">
    <div class="section-container">
        <h2 class="section-title">The Nethara</h2>

        <?php if (!empty($members)): ?>
            <div class="barracks-grid">
                <?php foreach ($members as $member): ?>
                    <div class="member-card">
                        <div class="member-avatar-wrapper">
                            <?php if (!empty($member['profile_photo'])): ?>
                                <img src="<?= base_url('assets/uploads/profiles/' . esc(basename($member['profile_photo']))) ?>"
                                    alt="" class="member-avatar">
                            <?php else: ?>
                                <div class="avatar-fallback" style="display: flex;"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="member-info">
                            <div class="member-name">
                                <?= esc($member['nama_lengkap']) ?>
                                <?php if (($member['sanctuary_role'] ?? '') === 'hosa'): ?>
                                    <span class="member-role-tag hosa">ðŸ‘‘</span>
                                <?php elseif (($member['sanctuary_role'] ?? '') === 'vizier'): ?>
                                    <span class="member-role-tag vizier">âš”ï¸</span>
                                <?php endif; ?>
                            </div>
                            <div class="member-username">@
                                <?= esc($member['username']) ?>
                            </div>
                        </div>
                        <div class="member-pp">
                            <?= number_format($member['total_pp']) ?> PP
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; color: #888; padding: 2rem;">
                <i class="fas fa-users-slash fa-3x" style="opacity: 0.5; margin-bottom: 1rem;"></i>
                <p>No members found in this Sanctuary.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Back Navigation -->
<div style="text-align: center; padding: 2rem 0 4rem; background: #000;">
    <a href="<?= base_url('world') ?>" class="back-link">
        <i class="fas fa-map-marked-alt"></i> BACK TO WORLD MAP
    </a>
</div>

<?= $this->endSection() ?>