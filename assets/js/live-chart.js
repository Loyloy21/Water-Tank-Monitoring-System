/**
 * Live Water Level Chart Controller using Chart.js
 * Generates an area trend graph displaying real-time water percentage over time
 */

class LiveWaterChart {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;
        this.chart = null;
        this.initChart();
    }

    initChart() {
        const ctx = this.canvas.getContext('2d');

        // Create gradient fill
        const gradient = ctx.createLinearGradient(0, 0, 0, 240);
        gradient.addColorStop(0, 'rgba(60, 126, 218, 0.45)');
        gradient.addColorStop(1, 'rgba(60, 126, 218, 0.02)');

        const currentPointBadgePlugin = {
            id: 'currentPointBadge',
            afterDatasetsDraw(chart) {
                const { ctx, data } = chart;
                const meta = chart.getDatasetMeta(0);
                if (!meta || !meta.data || meta.data.length === 0) return;
                const lastPoint = meta.data[meta.data.length - 1];
                if (!lastPoint) return;
                const val = data.datasets[0].data[data.datasets[0].data.length - 1];
                if (val === undefined || val === null) return;

                const text = `${Math.round(val)}%`;
                ctx.save();
                ctx.font = 'bold 11px "Plus Jakarta Sans", -apple-system, sans-serif';
                const textWidth = ctx.measureText(text).width;
                const boxWidth = textWidth + 14;
                const boxHeight = 20;
                const x = Math.min(chart.width - boxWidth - 4, Math.max(lastPoint.x - boxWidth / 2, 4));
                const y = Math.max(lastPoint.y - boxHeight - 8, 4);

                // Draw vivid blue badge
                ctx.fillStyle = '#3C7EDA';
                ctx.beginPath();
                if (ctx.roundRect) {
                    ctx.roundRect(x, y, boxWidth, boxHeight, 10);
                } else {
                    ctx.rect(x, y, boxWidth, boxHeight);
                }
                ctx.fill();

                // Draw white badge text
                ctx.fillStyle = '#FFFFFF';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(text, x + boxWidth / 2, y + boxHeight / 2);
                ctx.restore();
            }
        };

        this.chart = new Chart(ctx, {
            type: 'line',
            plugins: [currentPointBadgePlugin],
            data: {
                labels: ['09:30', '09:45', '10:00', '10:15', '10:30'],
                datasets: [{
                    label: 'Water Level (%)',
                    data: [75, 72, 68, 70, 72],
                    borderColor: '#3C7EDA',
                    borderWidth: 2.5,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#FFFFFF',
                    pointBorderColor: '#3C7EDA',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#0B233E',
                        titleFont: { family: 'Plus Jakarta Sans', size: 12, weight: '700' },
                        bodyFont: { family: 'Plus Jakarta Sans', size: 13, weight: '600' },
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return `Water Level: ${context.parsed.y}%`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#686C84',
                            font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' }
                        }
                    },
                    y: {
                        min: 0,
                        max: 100,
                        grid: {
                            color: '#F1F5F9',
                            drawBorder: false
                        },
                        ticks: {
                            stepSize: 25,
                            color: '#686C84',
                            font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' },
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    updateData(labels, percentages) {
        if (!this.chart) return;
        this.chart.data.labels = labels;
        this.chart.data.datasets[0].data = percentages;
        this.chart.update('none'); // Update without full redraw glitch
    }

    addPoint(label, percentage) {
        if (!this.chart) return;
        const labels = this.chart.data.labels;
        const data = this.chart.data.datasets[0].data;

        labels.push(label);
        data.push(percentage);

        if (labels.length > 20) {
            labels.shift();
            data.shift();
        }

        this.chart.update();
    }

    resize() {
        if (this.chart) {
            this.chart.resize();
        }
    }
}
