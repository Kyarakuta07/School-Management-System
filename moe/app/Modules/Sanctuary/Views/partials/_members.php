<?php
/**
 * sanctuary/_members.php
 * Displays sanctuary leadership and full member list.
 */

// Local helper for avatars (passed from view or redefined for safety if needed)
$safeAvatar = function ($photo) {
    if (empty($photo))
        return '';
    $safe = basename($photo);
    if (!preg_match('/^[a-zA-Z0-9_\-]+\.(jpg|jpeg|png|gif|webp)$/i', $safe))
        return '';
    return base_url('assets/uploads/profiles/' . $safe);
};
?>

<!-- LEADERSHIP -->
<div class="control-card">
    <div class="card-header"><i class="fas fa-crown"></i>
        <h3>Leadership</h3>
    </div>
    <div class="card-body">
        <div class="leader-row">
            <?php $hosaAvatar = $hosa ? $safeAvatar($hosa['profile_photo'] ?? '') : ''; ?>
            <?php if ($hosaAvatar): ?>
                <img src="<?= esc($hosaAvatar) ?>" alt="Hosa" class="leader-avatar">
            <?php else: ?>
                <div class="leader-avatar-fallback"><i class="fas fa-crown"></i></div>
            <?php endif; ?>
            <div class="leader-details">
                <div class="leader-name">
                    <?= $hosa ? esc($hosa['nama_lengkap']) : 'Vacant' ?>
                </div>
                <div class="leader-role">Hosa (Leader)</div>
            </div>
        </div>
        <?php if (!empty($viziers)): ?>
            <?php foreach ($viziers as $vizier): ?>
                <?php $vizierAvatar = $safeAvatar($vizier['profile_photo'] ?? ''); ?>
                <div class="leader-row">
                    <?php if ($vizierAvatar): ?>
                        <img src="<?= esc($vizierAvatar) ?>" alt="Vizier" class="leader-avatar">
                    <?php else: ?>
                        <div class="leader-avatar-fallback"><i class="fas fa-shield-alt"></i></div>
                    <?php endif; ?>
                    <div class="leader-details">
                        <div class="leader-name">
                            <?= esc($vizier['nama_lengkap']) ?>
                        </div>
                        <div class="leader-role">Vizier</div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="leader-row" style="opacity: 0.5;">
                <div class="leader-avatar-fallback"><i class="fas fa-user-slash"></i></div>
                <div class="leader-details">
                    <div class="leader-name">Vacant</div>
                    <div class="leader-role">Vizier</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- BARRACKS -->
<div class="control-card full-width">
    <div class="card-header"><i class="fas fa-users"></i>
        <h3>The Nethara (
            <?= number_format($memberCount) ?> Members)
        </h3>
    </div>
    <div class="card-body">
        <?php if (!empty($members)): ?>
            <div class="barracks-grid">
                <?php foreach ($members as $member): ?>
                    <?php $avatar = $safeAvatar($member['profile_photo'] ?? ''); ?>
                    <div class="member-card">
                        <?php if ($avatar): ?>
                            <img src="<?= esc($avatar) ?>" alt="" class="member-avatar">
                        <?php else: ?>
                            <div class="member-avatar-fallback"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                        <div class="member-info">
                            <div class="member-name">
                                <?= esc($member['nama_lengkap']) ?>
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
            <p style="color: #888; text-align: center;">No members found.</p>
        <?php endif; ?>
    </div>
</div>