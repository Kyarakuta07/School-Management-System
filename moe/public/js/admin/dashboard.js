/**
 * Admin: Dashboard — area chart for member distribution
 * Requires globals: CHART_LABELS, CHART_DATA (injected by view)
 */

var options = {
    series: [{ name: 'Members', data: CHART_DATA }],
    chart: {
        type: 'area', height: 320, fontFamily: 'Lato, sans-serif',
        background: 'transparent', toolbar: { show: false }
    },
    colors: ['#DAA520'],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.1, stops: [0, 90, 100],
            colorStops: [
                { offset: 0, color: '#DAA520', opacity: 0.5 },
                { offset: 100, color: '#DAA520', opacity: 0 }
            ]
        }
    },
    theme: { mode: 'dark' },
    xaxis: {
        categories: CHART_LABELS,
        labels: { style: { colors: '#e0e0e0', fontSize: '12px', fontFamily: 'Lato, sans-serif' } },
        axisBorder: { show: false }, axisTicks: { show: false }
    },
    yaxis: { labels: { style: { colors: '#aaa' } } },
    grid: { borderColor: '#333', strokeDashArray: 4 }
};

var chart = new ApexCharts(document.querySelector("#area-chart"), options);
chart.render();
