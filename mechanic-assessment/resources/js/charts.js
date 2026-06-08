import Chart from 'chart.js/auto';

const palette = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];

const centerTextPlugin = {
    id: 'centerText',
    beforeDraw(chart) {
        const { width, height, ctx } = chart;
        ctx.save();
        const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
        ctx.font = 'bold 28px Figtree, sans-serif';
        ctx.fillStyle = '#111827';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(total, width / 2, height / 2 - 8);
        ctx.font = '13px Figtree, sans-serif';
        ctx.fillStyle = '#6b7280';
        ctx.fillText('Total', width / 2, height / 2 + 18);
        ctx.restore();
    }
};

export function initAdminCharts(data) {
    if (document.getElementById('donutChart')) {
        new Chart(document.getElementById('donutChart'), {
            type: 'doughnut',
            plugins: [centerTextPlugin],
            data: {
                labels: ['Selesai', 'Terblokir', 'Berjalan'],
                datasets: [{
                    data: [data.submitted, data.blocked, data.pending],
                    backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 20, usePointStyle: true, pointStyle: 'circle', font: { size: 12, family: 'Figtree' } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    if (document.getElementById('lineChart')) {
        new Chart(document.getElementById('lineChart'), {
            type: 'line',
            data: {
                labels: data.dailyLabels,
                datasets: [{
                    label: 'Assessment',
                    data: data.dailyTotals,
                    borderColor: '#6366f1',
                    backgroundColor: function (ctx) {
                        if (!ctx.chart.chartArea) return 'rgba(99,102,241,0.08)';
                        const gradient = ctx.chart.ctx.createLinearGradient(0, ctx.chart.chartArea.top, 0, ctx.chart.chartArea.bottom);
                        gradient.addColorStop(0, 'rgba(99,102,241,0.25)');
                        gradient.addColorStop(1, 'rgba(99,102,241,0.01)');
                        return gradient;
                    },
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    borderWidth: 2.5,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleFont: { size: 12, family: 'Figtree' },
                        bodyFont: { size: 12, family: 'Figtree' },
                        padding: 10,
                        cornerRadius: 8,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0, font: { size: 11, family: 'Figtree' } },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        ticks: { maxRotation: 45, font: { size: 10, family: 'Figtree' } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    if (document.getElementById('barChart')) {
        const barColors = data.packageScores.map((v) => {
            if (v >= 80) return '#10b981';
            if (v >= 60) return '#f59e0b';
            return '#ef4444';
        });

        new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: data.packageLabels,
                datasets: [{
                    label: 'Rata-rata Nilai',
                    data: data.packageScores,
                    backgroundColor: barColors,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleFont: { size: 12, family: 'Figtree' },
                        bodyFont: { size: 12, family: 'Figtree' },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) { return ` ${ctx.parsed.y}%`; }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 11, family: 'Figtree' } } },
                    x: { grid: { display: false }, ticks: { font: { size: 10, family: 'Figtree' } } }
                }
            }
        });
    }

    if (document.getElementById('polarChart')) {
        new Chart(document.getElementById('polarChart'), {
            type: 'polarArea',
            data: {
                labels: data.categoryLabels,
                datasets: [{
                    data: data.categoryTotals,
                    backgroundColor: palette.slice(0, data.categoryLabels.length).map(c => c + 'CC'),
                    borderColor: palette.slice(0, data.categoryLabels.length),
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 14, usePointStyle: true, pointStyle: 'circle', font: { size: 11, family: 'Figtree' } }
                    },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        bodyFont: { size: 12, family: 'Figtree' },
                        padding: 10,
                        cornerRadius: 8,
                    }
                },
                scales: {
                    r: {
                        ticks: { stepSize: 1, precision: 0, font: { size: 10, family: 'Figtree' }, backdropColor: 'transparent' },
                        grid: { color: 'rgba(0,0,0,0.06)' }
                    }
                }
            }
        });
    }

    if (document.getElementById('radarChart')) {
        new Chart(document.getElementById('radarChart'), {
            type: 'radar',
            data: {
                labels: data.radarLabels,
                datasets: [{
                    label: 'Rata-rata Nilai',
                    data: data.radarValues,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.15)',
                    fill: true,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    borderWidth: 2.5,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 16, usePointStyle: true, font: { size: 12, family: 'Figtree' } }
                    },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        bodyFont: { size: 12, family: 'Figtree' },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) { return ` ${ctx.label}: ${ctx.parsed.r}%`; }
                        }
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { stepSize: 20, font: { size: 10, family: 'Figtree' }, backdropColor: 'transparent' },
                        grid: { color: 'rgba(0,0,0,0.07)' },
                        angleLines: { color: 'rgba(0,0,0,0.07)' },
                        pointLabels: { font: { size: 11, family: 'Figtree' } }
                    }
                }
            }
        });
    }
}
