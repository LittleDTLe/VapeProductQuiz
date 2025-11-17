jQuery(document).ready(function ($) {

    /**
     * Creates a modern bar chart
     */
    function createModernChart(ctx, labels, data, chartLabel, gradientColors) {
        var gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, gradientColors[0]);
        gradient.addColorStop(1, gradientColors[1]);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: chartLabel,
                    data: data,
                    backgroundColor: gradient,
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
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
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            color: '#666'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            precision: 0,
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            color: '#666',
                            padding: 10
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
    
    // Top Types Chart
    // vvChartData is passed from wp_localize_script
    var ctxTypes = document.getElementById('vvTopTypesChart');
    if (ctxTypes && vvChartData.type_labels.length > 0) {
        createModernChart(
            ctxTypes.getContext('2d'),
            vvChartData.type_labels,
            vvChartData.type_data,
            vvChartData.i18n.searches,
            ['rgba(102, 126, 234, 0.8)', 'rgba(118, 75, 162, 0.8)']
        );
    }

    // Top Primary Ingredients Chart
    var ctxPrimary = document.getElementById('vvTopPrimaryChart');
    if (ctxPrimary && vvChartData.primary_labels.length > 0) {
        createModernChart(
            ctxPrimary.getContext('2d'),
            vvChartData.primary_labels,
            vvChartData.primary_data,
            vvChartData.i18n.searches,
            ['rgba(102, 126, 234, 0.8)', 'rgba(118, 75, 162, 0.8)']
        );
    }
});