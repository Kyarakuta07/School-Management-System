document.addEventListener("DOMContentLoaded", function () {
    // Pengaturan global untuk font dan warna
    const textColor = 'var(--subtle-text-color)';
    const gridColor = 'var(--border-color)';
    const accentColor = 'var(--accent-color)';

    // --- Grafik Aktivitas per Sanctuary (SEKARANG MENJADI LINE CHART) ---
    const areaChartEl = document.querySelector("#area-chart");
    if (areaChartEl && typeof sanctuaryChartData !== 'undefined') {
        const labels = sanctuaryChartData.map(item => item.nama_sanctuary);
        const data = sanctuaryChartData.map(item => item.jumlah);

        const options = {
            series: [{
                name: 'Jumlah Nethera Aktif',
                data: data
            }],
            chart: {
                height: 350,
                type: 'line',
                toolbar: {
                    show: false
                },
                foreColor: textColor,
                zoom: {
                    enabled: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3,
                colors: [accentColor]
            },
            xaxis: {
                categories: labels,
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return parseInt(val); // Tampilkan angka bulat
                    }
                }
            },
            markers: {
                size: 5,
                colors: [accentColor],
                strokeColors: 'var(--card-bg-color)',
                strokeWidth: 2
            },
            grid: {
                borderColor: gridColor
            },
            tooltip: {
                theme: 'dark'
            }
        };

        const chart = new ApexCharts(areaChartEl, options);
        chart.render();
    }
});

