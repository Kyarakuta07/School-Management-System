<!-- Hero Header -->
<header class="hero-header">
    <div class="header-content">
        <div class="header-left">
            <a href="<?= base_url('beranda') ?>" class="back-btn" title="Back to Dashboard">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="header-title-group">
                <h1 class="header-title">Pet Companion</h1>
                <span class="header-subtitle">Virtual Pet System</span>
            </div>
        </div>
        <div class="header-right">
            <div class="gold-display" title="Your Gold">
                <i class="fas fa-coins"></i>
                <span id="user-gold"><?= number_format($userGold) ?></span>
            </div>
            <button class="help-btn btn-icon" onclick="document.getElementById('help-modal').classList.add('show')"
                title="Help">
                <i class="fas fa-question-circle"></i>
            </button>
        </div>
    </div>
</header>