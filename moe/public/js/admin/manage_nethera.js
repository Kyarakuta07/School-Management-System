/**
 * Admin: Manage Nethera — client-side filter and search
 */

// Filter by status dropdown
function filterStatus(status) {
    const rows = document.querySelectorAll('#netheraTableBody tr[data-status]');
    rows.forEach(row => {
        row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
    });
}

// Search input
document.getElementById('searchInput')?.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    const rows = document.querySelectorAll('#netheraTableBody tr[data-status]');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
