/**
 * Admin: Manage Classes — sanctuary chart + grade search
 * Requires globals: CHART_LABELS, CHART_POINTS (injected by view)
 */

// Sanctuary Chart
if (typeof CHART_LABELS !== 'undefined' && CHART_LABELS.length > 0) {
    var chartOptions = {
        series: [{ name: 'Points', data: CHART_POINTS.map(Number) }],
        chart: {
            type: 'bar', height: 300, fontFamily: 'Lato, sans-serif',
            background: 'transparent', toolbar: { show: false }
        },
        colors: ['#DAA520'],
        plotOptions: { bar: { borderRadius: 4, horizontal: false } },
        dataLabels: { enabled: true, style: { colors: ['#fff'] } },
        theme: { mode: 'dark' },
        xaxis: { categories: CHART_LABELS, labels: { style: { colors: '#e0e0e0' } } },
        yaxis: { labels: { style: { colors: '#aaa' } } },
        grid: { borderColor: '#333', strokeDashArray: 4 }
    };
    new ApexCharts(document.querySelector("#sanctuaryChart"), chartOptions).render();
}

// Grade search
document.getElementById('gradeSearchInput')?.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#gradeTableBody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
