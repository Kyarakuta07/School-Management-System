<section class="guild-section">
    <h2 class="section-title">The Throne Room</h2>
    <div class="throne-room">
        <?php
        $hosa = null;
        $viziers = [];
        foreach ($leaders as $l) {
            if ($l['sanctuary_role'] === 'hosa')
                $hosa = $l;
            else
                $viziers[] = $l;
        }
        ?>
        <!-- HOSA -->
        <div class="leader-card hosa-card">
            <div class="role-badge">HOSA</div>
            <?php $hosaAvatar = get_safe_avatar($hosa['profile_photo'] ?? ''); ?>
            <div class="leader-avatar-wrapper">
                <?php if ($hosaAvatar): ?>
                    <img src="<?= esc($hosaAvatar) ?>" alt="Hosa" class="leader-avatar">
                <?php else: ?>
                    <div class="leader-avatar leader-avatar-fallback"><i class="fas fa-crown"></i></div>
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

        <!-- VIZIERS -->
        <?php if (!empty($viziers)): ?>
            <?php foreach ($viziers as $vizier): ?>
                <?php $vizierAvatar = get_safe_avatar($vizier['profile_photo'] ?? ''); ?>
                <div class="leader-card">
                    <div class="role-badge" style="background: silver;">VIZIER</div>
                    <div class="leader-avatar-wrapper">
                        <?php if ($vizierAvatar): ?>
                            <img src="<?= esc($vizierAvatar) ?>" alt="Vizier" class="leader-avatar">
                        <?php else: ?>
                            <div class="leader-avatar leader-avatar-fallback"><i class="fas fa-shield-alt"></i></div>
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
                <div class="leader-avatar leader-avatar-fallback"><i class="fas fa-user-slash"></i></div>
                <div class="leader-name">Vacant</div>
                <div class="leader-username">No vizier assigned</div>
            </div>
        <?php endif; ?>
    </div>
</section>