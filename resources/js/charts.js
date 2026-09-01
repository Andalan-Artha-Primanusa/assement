import Chart from 'chart.js/auto';

Chart.defaults.font.family = 'Figtree, ui-sans-serif, system-ui, sans-serif';
Chart.defaults.color = '#4b5563';

const statusColors = ['#10b981', '#ef4444', '#f59e0b', '#64748b'];
const categoryColors = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6', '#ec4899', '#64748b'];

const chartInstances = {};

const centerTextPlugin = {
    id: 'centerText',
    beforeDraw(chart) {
        if (chart.config.type !== 'doughnut') {
            return;
        }

        const { ctx, chartArea } = chart;
        const dataset = chart.data.datasets[0];
        const total = dataset.data.reduce((sum, value) => sum + Number(value || 0), 0);
        const centerX = (chartArea.left + chartArea.right) / 2;
        const centerY = (chartArea.top + chartArea.bottom) / 2;

        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = '#111827';
        ctx.font = '700 30px Figtree, ui-sans-serif';
        ctx.fillText(total, centerX, centerY - 8);
        ctx.fillStyle = '#6b7280';
        ctx.font = '500 12px Figtree, ui-sans-serif';
        ctx.fillText('Assessment', centerX, centerY + 18);
        ctx.restore();
    },
};

function resetChart(id) {
    if (chartInstances[id]) {
        chartInstances[id].destroy();
    }
}

function truncateLabel(value, limit = 24) {
    const label = String(value);

    return label.length > limit ? `${label.slice(0, limit - 1)}...` : label;
}

function tooltipOptions(extra = {}) {
    return {
        backgroundColor: '#111827',
        titleColor: '#ffffff',
        bodyColor: '#e5e7eb',
        borderColor: 'rgba(255, 255, 255, 0.12)',
        borderWidth: 1,
        padding: 12,
        cornerRadius: 8,
        displayColors: true,
        boxPadding: 4,
        ...extra,
    };
}

function gridColor() {
    return 'rgba(148, 163, 184, 0.18)';
}

function createLineChart(data) {
    const element = document.getElementById('lineChart');

    if (!element) {
        return;
    }

    resetChart('lineChart');

    chartInstances.lineChart = new Chart(element, {
        type: 'line',
        data: {
            labels: data.dailyLabels || [],
            datasets: [{
                label: 'Assessment selesai',
                data: data.dailyTotals || [],
                borderColor: '#4f46e5',
                backgroundColor(context) {
                    const { chart } = context;

                    if (!chart.chartArea) {
                        return 'rgba(79, 70, 229, 0.12)';
                    }

                    const gradient = chart.ctx.createLinearGradient(0, chart.chartArea.top, 0, chart.chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(79, 70, 229, 0.24)');
                    gradient.addColorStop(0.7, 'rgba(79, 70, 229, 0.05)');
                    gradient.addColorStop(1, 'rgba(79, 70, 229, 0)');

                    return gradient;
                },
                fill: true,
                tension: 0.32,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointBackgroundColor: '#4f46e5',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                borderWidth: 3,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: { display: false },
                tooltip: tooltipOptions(),
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        stepSize: 1,
                        padding: 8,
                    },
                    grid: {
                        color: gridColor(),
                        drawBorder: false,
                    },
                    border: {
                        display: false,
                    },
                },
                x: {
                    ticks: {
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 8,
                        padding: 8,
                    },
                    grid: {
                        display: false,
                    },
                    border: {
                        display: false,
                    },
                },
            },
        },
    });
}

function createDoughnutChart(data) {
    const element = document.getElementById('donutChart');

    if (!element) {
        return;
    }

    resetChart('donutChart');

    chartInstances.donutChart = new Chart(element, {
        type: 'doughnut',
        plugins: [centerTextPlugin],
        data: {
            labels: ['Selesai', 'Terblokir', 'Berjalan', 'Belum Test'],
            datasets: [{
                data: [data.submitted || 0, data.blocked || 0, data.pending || 0, data.notStarted || 0],
                backgroundColor: statusColors,
                borderColor: '#ffffff',
                borderWidth: 4,
                hoverOffset: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '74%',
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: tooltipOptions({
                    callbacks: {
                        label(context) {
                            const values = context.dataset.data;
                            const total = values.reduce((sum, value) => sum + Number(value || 0), 0);
                            const percent = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : '0.0';

                            return ` ${context.label}: ${context.parsed} (${percent}%)`;
                        },
                    },
                }),
            },
        },
    });
}

function createPackageChart(data) {
    const element = document.getElementById('packageChart');

    if (!element) {
        return;
    }

    resetChart('packageChart');

    const labels = data.packageLabels || [];
    const values = data.packageScores || [];

    chartInstances.packageChart = new Chart(element, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Rata-rata nilai',
                data: values,
                backgroundColor: values.map((value) => {
                    if (value >= 80) {
                        return '#10b981';
                    }

                    if (value >= 60) {
                        return '#f59e0b';
                    }

                    return '#ef4444';
                }),
                borderRadius: 8,
                borderSkipped: false,
                barThickness: 22,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: tooltipOptions({
                    callbacks: {
                        title(items) {
                            return labels[items[0].dataIndex] || '';
                        },
                        label(context) {
                            return ` Nilai: ${context.parsed.x}%`;
                        },
                    },
                }),
            },
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: (value) => `${value}%`,
                        padding: 8,
                    },
                    grid: {
                        color: gridColor(),
                        drawBorder: false,
                    },
                    border: {
                        display: false,
                    },
                },
                y: {
                    ticks: {
                        callback(value) {
                            return truncateLabel(labels[value] || '');
                        },
                        padding: 8,
                    },
                    grid: {
                        display: false,
                    },
                    border: {
                        display: false,
                    },
                },
            },
        },
    });
}

function createCategoryChart(data) {
    const element = document.getElementById('categoryChart');

    if (!element) {
        return;
    }

    resetChart('categoryChart');

    const labels = data.categoryLabels || [];
    const values = data.categoryTotals || [];

    chartInstances.categoryChart = new Chart(element, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Jumlah soal',
                data: values,
                backgroundColor: labels.map((_, index) => categoryColors[index % categoryColors.length]),
                borderRadius: 8,
                borderSkipped: false,
                barThickness: 20,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: tooltipOptions({
                    callbacks: {
                        title(items) {
                            return labels[items[0].dataIndex] || '';
                        },
                        label(context) {
                            return ` ${context.parsed.x} soal`;
                        },
                    },
                }),
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        stepSize: 1,
                        padding: 8,
                    },
                    grid: {
                        color: gridColor(),
                        drawBorder: false,
                    },
                    border: {
                        display: false,
                    },
                },
                y: {
                    ticks: {
                        callback(value) {
                            return truncateLabel(labels[value] || '');
                        },
                        padding: 8,
                    },
                    grid: {
                        display: false,
                    },
                    border: {
                        display: false,
                    },
                },
            },
        },
    });
}

export function initAdminCharts(data) {
    createLineChart(data);
    createDoughnutChart(data);
    createPackageChart(data);
    createCategoryChart(data);
}
