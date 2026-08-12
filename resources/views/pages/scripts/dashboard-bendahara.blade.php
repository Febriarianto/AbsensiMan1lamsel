<script>
    (function () {
        const root = document.getElementById('view-bendahara-dashboard');
        if (!root) {
            return;
        }

        const payload = @json($bendaharaDashboard ?? []);
        let bendaharaTypeChart = null;
        let bendaharaStatusChart = null;
        let bendaharaChartsRegistered = false;

        function registerChartPlugins() {
            if (bendaharaChartsRegistered) {
                return;
            }

            if (typeof Chart !== 'undefined' && typeof Chart.register === 'function' && typeof ChartDataLabels !== 'undefined') {
                Chart.register(ChartDataLabels);
            }

            bendaharaChartsRegistered = true;
        }

        function formatCompactRupiah(value) {
            const amount = Number(value || 0);
            const absolute = Math.abs(amount);

            if (absolute >= 1000000000) {
                return `${(amount / 1000000000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} M`;
            }

            if (absolute >= 1000000) {
                return `${(amount / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} jt`;
            }

            if (absolute >= 1000) {
                return `${(amount / 1000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} rb`;
            }

            return amount.toLocaleString('id-ID');
        }

        function formatRupiah(value) {
            return `Rp ${Number(value || 0).toLocaleString('id-ID')}`;
        }

        function buildRekeningUrl(params = {}) {
            const base = String(window.APP_ROUTES?.tabunganSiswaRekening || @json(route('tabungan-siswa.rekening.index')));
            const url = new URL(base, window.location.origin);

            Object.entries(params || {}).forEach(([key, value]) => {
                if (value !== null && value !== undefined && String(value).trim() !== '') {
                    url.searchParams.set(key, String(value));
                }
            });

            return `${url.pathname}${url.search}`;
        }

        function navigateToRekening(params = {}) {
            window.location.href = buildRekeningUrl(params);
        }

        function renderTypeChart() {
            const canvas = document.getElementById('bendaharaTypeChart');
            const rows = Array.isArray(payload.type_chart) ? payload.type_chart : [];
            if (!canvas || rows.length === 0 || typeof Chart === 'undefined') {
                return;
            }

            registerChartPlugins();

            if (bendaharaTypeChart) {
                bendaharaTypeChart.destroy();
            }

            const context = canvas.getContext('2d');
            if (!context) {
                return;
            }

            const palette = ['#F59E0B', '#FBBF24', '#FCD34D', '#FDBA74', '#FB923C', '#F97316'];

            bendaharaTypeChart = new Chart(context, {
                type: 'bar',
                data: {
                    labels: rows.map((row) => String(row.label || '-')),
                    datasets: [{
                        data: rows.map((row) => Number(row.value || 0)),
                        backgroundColor: rows.map((row, index) => palette[index % palette.length]),
                        borderRadius: 12,
                        borderSkipped: false,
                        barThickness: 26,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 650
                    },
                    onHover(event, elements, chart) {
                        chart.canvas.style.cursor = elements.length ? 'pointer' : 'default';
                    },
                    onClick(event, elements, chart) {
                        if (!elements.length) {
                            return;
                        }

                        const point = elements[0];
                        const row = rows[point.index];
                        if (!row || Number(row.value || 0) <= 0 || Number(row.id || 0) <= 0) {
                            return;
                        }

                        navigateToRekening({ jenis_tabungan_id: row.id });
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                title(items) {
                                    return items?.[0]?.label || '';
                                },
                                label(context) {
                                    const row = rows[context.dataIndex] || {};
                                    return [
                                        `Saldo: ${formatRupiah(row.value || 0)}`,
                                        `Rekening: ${Number(row.account_count || 0).toLocaleString('id-ID')}`
                                    ];
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'right',
                            color: '#92400E',
                            formatter(value) {
                                return value > 0 ? formatCompactRupiah(value) : '';
                            },
                            font: {
                                weight: 'bold',
                                size: 11
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: '#F3F4F6'
                            },
                            ticks: {
                                color: '#6B7280',
                                callback(value) {
                                    return formatCompactRupiah(value);
                                }
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#374151',
                                font: {
                                    weight: '600'
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderStatusChart() {
            const canvas = document.getElementById('bendaharaStatusChart');
            const rows = Array.isArray(payload.status_chart) ? payload.status_chart : [];
            if (!canvas || rows.length === 0 || typeof Chart === 'undefined') {
                return;
            }

            const total = rows.reduce((sum, row) => sum + Number(row.value || 0), 0);
            if (total <= 0) {
                return;
            }

            registerChartPlugins();

            if (bendaharaStatusChart) {
                bendaharaStatusChart.destroy();
            }

            const context = canvas.getContext('2d');
            if (!context) {
                return;
            }

            bendaharaStatusChart = new Chart(context, {
                type: 'doughnut',
                data: {
                    labels: rows.map((row) => String(row.label || '-')),
                    datasets: [{
                        data: rows.map((row) => Number(row.value || 0)),
                        backgroundColor: ['#10B981', '#94A3B8'],
                        borderColor: '#FFFFFF',
                        borderWidth: 4,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    onHover(event, elements, chart) {
                        chart.canvas.style.cursor = elements.length ? 'pointer' : 'default';
                    },
                    onClick(event, elements) {
                        if (!elements.length) {
                            return;
                        }

                        const row = rows[elements[0].index];
                        if (!row || Number(row.value || 0) <= 0) {
                            return;
                        }

                        navigateToRekening({ status: row.key });
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 10,
                                padding: 18
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label(context) {
                                    const value = Number(context.raw || 0);
                                    const percent = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${context.label}: ${value.toLocaleString('id-ID')} rekening (${percent}%)`;
                                }
                            }
                        },
                        datalabels: {
                            color: '#111827',
                            formatter(value) {
                                if (!value) {
                                    return '';
                                }

                                return `${value}`;
                            },
                            font: {
                                weight: 'bold',
                                size: 11
                            }
                        }
                    }
                }
            });
        }

        function bootBendaharaDashboardCharts() {
            renderTypeChart();
            renderStatusChart();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootBendaharaDashboardCharts);
            return;
        }

        bootBendaharaDashboardCharts();
    })();
</script>
