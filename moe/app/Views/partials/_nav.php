<!-- TOP NAVIGATION -->
<nav class="top-nav-menu">
    <a href="<?= base_url('beranda') ?>" class="nav-btn <?= ($activePage ?? '') === 'beranda' ? 'active' : '' ?>">
        <i class="fa-solid fa-home"></i><span>Home</span>
    </a>
    <a href="<?= base_url('class') ?>" class="nav-btn <?= ($activePage ?? '') === 'class' ? 'active' : '' ?>">
        <i class="fa-solid fa-book-open"></i><span>Class</span>
    </a>
    <a href="<?= base_url('pet') ?>" class="nav-btn <?= ($activePage ?? '') === 'pet' ? 'active' : '' ?>">
        <i class="fa-solid fa-paw"></i><span>Pet</span>
    </a>
    <a href="<?= base_url('trapeza') ?>" class="nav-btn <?= ($activePage ?? '') === 'trapeza' ? 'active' : '' ?>">
        <i class="fa-solid fa-credit-card"></i><span>Trapeza</span>
    </a>
    <a href="<?= base_url('punishment') ?>" class="nav-btn <?= ($activePage ?? '') === 'punishment' ? 'active' : '' ?>">
        <i class="fa-solid fa-gavel"></i><span>Punishment</span>
    </a>
</nav>