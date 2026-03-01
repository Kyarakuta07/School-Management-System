<!-- BOTTOM NAVIGATION (Mobile Only) -->
<nav class="bottom-nav">
    <a href="<?= base_url('beranda') ?>"
        class="bottom-nav-item <?= ($activePage ?? '') === 'beranda' ? 'active' : '' ?>">
        <i class="fa-solid fa-home"></i>
        <span>Home</span>
    </a>
    <a href="<?= base_url('class') ?>" class="bottom-nav-item <?= ($activePage ?? '') === 'class' ? 'active' : '' ?>">
        <i class="fa-solid fa-book-open"></i>
        <span>Class</span>
    </a>
    <a href="<?= base_url('pet') ?>" class="bottom-nav-item <?= ($activePage ?? '') === 'pet' ? 'active' : '' ?>">
        <i class="fa-solid fa-paw"></i>
        <span>Pet</span>
    </a>
    <a href="<?= base_url('trapeza') ?>"
        class="bottom-nav-item <?= ($activePage ?? '') === 'trapeza' ? 'active' : '' ?>">
        <i class="fa-solid fa-credit-card"></i>
        <span>Bank</span>
    </a>
    <a href="<?= base_url('punishment') ?>"
        class="bottom-nav-item <?= ($activePage ?? '') === 'punishment' ? 'active' : '' ?>">
        <i class="fa-solid fa-gavel"></i>
        <span>Rules</span>
    </a>
</nav>