<section class="guild-section">
    <h2 class="section-title">The Nethara</h2>
    <?php if (!empty($members)): ?>
        <div class="barracks-grid">
            <?php foreach ($members as $member): ?>
                <?php $memberAvatar = get_safe_avatar($member['profile_photo'] ?? ''); ?>
                <div class="member-card">
                    <div>
                        <?php if ($memberAvatar): ?>
                            <img src="<?= esc($memberAvatar) ?>" alt="" class="member-avatar">
                        <?php else: ?>
                            <div class="avatar-fallback" style="display: flex;"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="member-info">
                        <div class="member-name">
                            <?= esc($member['nama_lengkap']) ?>
                            <?php if ($member['sanctuary_role'] === 'hosa'): ?><span>👑</span>
                            <?php elseif ($member['sanctuary_role'] === 'vizier'): ?><span>⚔️</span>
                            <?php endif; ?>
                        </div>
                        <div class="member-username">@
                            <?= esc($member['username']) ?>
                        </div>
                    </div>
                    <div class="member-pp">
                        <?= number_format($member['total_pp'] ?? 0) ?> PP
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; color: #888; padding: 2rem;">
            <i class="fas fa-users-slash fa-3x" style="opacity: 0.5; margin-bottom: 1rem;"></i>
            <p>No members found in this sanctuary.</p>
        </div>
    <?php endif; ?>
</section>