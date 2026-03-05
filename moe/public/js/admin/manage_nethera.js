/**
 * Admin: Manage Nethera — server-side filter and search
 */

// Navigate with filter & search query params
function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const q = document.getElementById('searchInput').value.trim();
    const params = new URLSearchParams();
    if (status !== 'all') params.set('status', status);
    if (q) params.set('q', q);
    const qs = params.toString();
    window.location.href = ASSET_BASE + 'admin/nethera' + (qs ? '?' + qs : '');
}

// Search on Enter key
document.getElementById('searchInput')?.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        applyFilters();
    }
});
