<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Analytics - Ordering Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
</head>
<body class="app-page">
    <nav class="app-nav no-print">
        <div class="app-nav-inner">
            <a class="app-brand" href="{{ route('analytics.orders') }}">Ordering Portal</a>

{{--            <a href="{{ route('dealers.index') }}" class="button-outline">--}}
{{--                Exit Impersonation--}}
{{--            </a>--}}
        </div>
    </nav>

    @php
        $chartEntries = $chartConfig['entries'] ?? [];
        $trendOptions = [
            'day' => 'Day',
            'week' => 'Week',
            'month' => 'Month',
            'year' => 'Years',
        ];
    @endphp

    <div class="w-full px-4 pb-6 lg:px-6">
        <section class="py-5">
            <div class="mb-4 flex flex-wrap gap-3">
                <a href="{{ route('my.orders') }}" class="button-outline">My Orders</a>
                <a href="{{ route('my.leads') }}" class="button-outline">My Leads</a>
                <a href="{{ route('analytics.orders') }}" class="button-primary">Analytics</a>
            </div>

            <div class="space-y-4">
                <div class="surface-panel">
                    <div class="surface-panel__header items-start gap-6">

                        <!-- LEFT SIDE -->
                        <div class="w-full lg:w-1/2">
                            <p class="section-heading">Analytics</p>

                            <h1 class="mt-1 text-xl font-semibold text-white">
                                Orders And Leads Dashboard
                            </h1>

                            <p class="mt-1 text-sm text-slate-400">
                                Compact staged analytics across dealer orders, payments, and leads.
                            </p>

                            @if($latestSyncAt)
                                <p class="mt-2 text-xs uppercase tracking-[0.16em] text-slate-500">
                                    Latest sync {{ \Illuminate\Support\Carbon::parse($latestSyncAt)->format('Y-m-d H:i') }}
                                </p>
                            @endif

                            <!-- FILTERS -->
                            <form
                                action="{{ route('analytics.orders') }}"
                                method="GET"
                                class="mt-5 grid gap-3 sm:grid-cols-2"
                            >
                                <div>
                                    <label class="label-text" for="dealer_scope">Dealer</label>
                                    <select id="dealer_scope" name="dealer_scope" class="input-field input-field--sm">
                                        <option value="">All dealers</option>
                                        @foreach($availableScopes as $scope)
                                            <option value="{{ $scope }}" @selected(($filters['dealer_scope'] ?? null) === $scope)>
                                                {{ $scope }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="label-text" for="status">Order Status</label>
                                    <select id="status" name="status" class="input-field input-field--sm">
                                        <option value="">All statuses</option>
                                        @foreach($availableOrderStatuses as $status)
                                            <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>
                                                {{ $status }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="label-text" for="date_from">From</label>
                                    <input id="date_from" type="date" name="date_from"
                                           value="{{ $filters['date_from'] ?? '' }}"
                                           class="input-field input-field--sm">
                                </div>

                                <div>
                                    <label class="label-text" for="date_to">To</label>
                                    <input id="date_to" type="date" name="date_to"
                                           value="{{ $filters['date_to'] ?? '' }}"
                                           class="input-field input-field--sm">
                                </div>

                                <div>
                                    <label class="label-text" for="chart">Pie Source</label>
                                    <select id="chart" name="chart" class="input-field input-field--sm">
                                        <option value="order_status" @selected(($filters['chart'] ?? null) === 'order_status')>Order Status</option>
                                        <option value="payment_status" @selected(($filters['chart'] ?? null) === 'payment_status')>Payment Status</option>
                                        <option value="lead_status" @selected(($filters['chart'] ?? null) === 'lead_status')>Lead Status</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="label-text" for="chart_metric">Pie Metric</label>
                                    <select id="chart_metric" name="chart_metric" class="input-field input-field--sm">
                                        <option value="count" @selected(($filters['chart_metric'] ?? null) === 'count')>Count</option>
                                        <option value="amount" @selected(($filters['chart_metric'] ?? null) === 'amount')>Amount</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="label-text" for="chart_limit">Entries</label>
                                    <input id="chart_limit" type="number" min="2" max="12"
                                           name="chart_limit"
                                           value="{{ $filters['chart_limit'] ?? 5 }}"
                                           class="input-field input-field--sm">
                                </div>

                                <!-- BUTTONS BELOW -->
                                <div class="sm:col-span-2 flex gap-2 mt-2">
                                    <button type="submit" class="button-primary flex-1">Apply</button>
                                    <a href="{{ route('analytics.orders') }}" class="button-secondary flex-1">Reset</a>
                                </div>
                            </form>
                        </div>

                        <!-- RIGHT SIDE -->
                        <div class="w-full lg:w-1/2">
                            <!-- Your new content goes here -->
                            <div class="surface-panel__header">
                                <div>
                                    <p class="section-heading">Pie Chart</p>
                                    <h2 class="mt-1 text-lg font-semibold text-white">{{ $chartConfig['title'] }}</h2>
                                    <p class="mt-1 text-sm text-slate-400">Metric: {{ $chartConfig['metric_label'] }}</p>
                                </div>
                            </div>
                            <div class="surface-panel__body">
                                <div class="grid gap-4 lg:grid-cols-[13rem_minmax(0,1fr)] lg:items-center">
                                    @if(count($chartEntries) > 0)
                                        <div class="mx-auto w-full max-w-[15rem]">
                                            <canvas
                                                id="analyticsPieChart"
                                                data-chart='@json($chartEntries)'
                                                data-metric="{{ $chartConfig['metric_label'] }}"
                                                data-selected-metric="{{ $chartConfig['selected_metric'] }}"
                                                data-total="{{ $chartConfig['total'] }}"
                                            ></canvas>
                                        </div>
                                    @endif

                                    <div class="space-y-2">
                                        @forelse($chartEntries as $entry)
                                            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2">
                                                <div class="flex items-center gap-3">
                                                    <span class="h-3 w-3 rounded-full" style="background-color: {{ $entry['color'] }}"></span>
                                                    <span class="text-sm font-medium text-slate-100">{{ $entry['label'] }}</span>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-sm font-semibold text-slate-100">
                                                        @if(($chartConfig['selected_metric'] ?? 'count') === 'amount')
                                                            ${{ number_format($entry['value'], 2) }}
                                                        @else
                                                            {{ number_format($entry['value']) }}
                                                        @endif
                                                    </div>
                                                    <div class="text-xs uppercase tracking-[0.16em] text-slate-500">{{ number_format($entry['percent'], 1) }}%</div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="empty-state rounded-xl border border-dashed border-slate-800 bg-slate-950/40">
                                                No chart data available for the current filters.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                    <div class="metric-card">
                        <p class="metric-label">Orders</p>
                        <p class="mt-1 text-xl font-semibold text-slate-100">{{ number_format($summary['order_count']) }}</p>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Leads</p>
                        <p class="mt-1 text-xl font-semibold text-slate-100">{{ number_format($summary['lead_count']) }}</p>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">With Customer</p>
                        <p class="mt-1 text-xl font-semibold text-slate-100">{{ number_format($summary['customer_attached_order_count']) }}</p>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Order Value</p>
                        <p class="mt-1 text-xl font-semibold text-slate-100">${{ number_format($summary['order_value'], 2) }}</p>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Paid</p>
                        <p class="mt-1 text-xl font-semibold text-slate-100">${{ number_format($summary['paid_value'], 2) }}</p>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Avg Order</p>
                        <p class="mt-1 text-xl font-semibold text-slate-100">${{ number_format($summary['avg_order_value'], 2) }}</p>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Conversion</p>
                        <p class="mt-1 text-xl font-semibold text-slate-100">
                            {{ $summary['conversion_rate'] === null ? 'N/A' : number_format($summary['conversion_rate'], 1).'%' }}
                        </p>
                    </div>
                </div>



                <div class="grid gap-4 xl:grid-cols-[minmax(0,0.86fr)_minmax(0,1.14fr)]">
                    <div class="surface-panel">
                        <div class="surface-panel__header">
                            <div>
                                <p class="section-heading">Leads</p>
                                <h2 class="mt-1 text-lg font-semibold text-white">Lead Status Breakdown</h2>
                            </div>
                        </div>
                        <div class="surface-panel__body space-y-2">
                            @forelse($leadStatusBreakdown as $row)
                                <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2.5">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-medium text-slate-100">{{ $row['status'] }}</span>
                                        <span class="text-xs uppercase tracking-[0.16em] text-slate-500">{{ number_format($row['total_leads']) }}</span>
                                    </div>
                                    <div class="mt-1 text-sm text-slate-400">${{ number_format($row['total_amount'], 2) }}</div>
                                </div>
                            @empty
                                <div class="empty-state rounded-xl border border-dashed border-slate-800 bg-slate-950/40">No lead snapshots match the current filters.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="surface-panel">
                        <div class="surface-panel__header">
                            <div>
                                <p class="section-heading">Dealers</p>
                                <h2 class="mt-1 text-lg font-semibold text-white">Dealer Performance</h2>
                            </div>
                        </div>
                        <div class="surface-panel__body">
                            <div class="table-shell">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Dealer</th>
                                            <th>Orders</th>
                                            <th>Leads</th>
                                            <th>Revenue</th>
                                            <th>Conversion</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($dealerPerformance as $row)
                                            <tr>
                                                <td>
                                                    <div class="font-semibold text-white">{{ $row['dealer_name'] }}</div>
                                                    <div class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-500">{{ $row['dealer_scope'] }}</div>
                                                </td>
                                                <td>{{ number_format($row['total_orders']) }}</td>
                                                <td>{{ number_format($row['total_leads']) }}</td>
                                                <td>${{ number_format($row['total_value'], 2) }}</td>
                                                <td>{{ $row['conversion_rate'] === null ? 'N/A' : number_format($row['conversion_rate'], 1).'%' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="empty-state">No dealer performance data available.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    <div class="surface-panel">
                        <div class="surface-panel__header">
                            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                                <div>
                                <p class="section-heading">Trend</p>
                                <h2 class="mt-1 text-lg font-semibold text-white">Orders By {{ $trendOptions[$orderTrendGranularity] ?? 'Day' }}</h2>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @foreach($trendOptions as $value => $label)
                                        <a
                                            href="{{ route('analytics.orders', array_merge(request()->query(), ['order_trend_granularity' => $value])) }}"
                                            class="{{ ($orderTrendGranularity ?? 'day') === $value ? 'button-primary' : 'button-outline' }}"
                                        >
                                            {{ $label }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="surface-panel__body">
                            @if(in_array($orderTrendGranularity, ['month', 'year'], true))
                                <div class="mb-5 h-72 rounded-2xl border border-slate-800 bg-slate-950/40 p-4">
                                    <canvas
                                        id="ordersTrendChart"
                                        data-trend='@json($orderTrendChart)'
                                        data-count-label="Orders"
                                        data-amount-label="Order Value"
                                    ></canvas>
                                </div>
                            @endif

                            <div class="table-shell">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Orders</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($orderTrend as $row)
                                            <tr>
                                                <td>{{ $row['date'] }}</td>
                                                <td>{{ number_format($row['total_orders']) }}</td>
                                                <td>${{ number_format($row['total_value'], 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="empty-state">No order trend data available.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="surface-panel">
                        <div class="surface-panel__header">
                            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                                <div>
                                <p class="section-heading">Trend</p>
                                <h2 class="mt-1 text-lg font-semibold text-white">Leads By {{ $trendOptions[$leadTrendGranularity] ?? 'Day' }}</h2>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @foreach($trendOptions as $value => $label)
                                        <a
                                            href="{{ route('analytics.orders', array_merge(request()->query(), ['lead_trend_granularity' => $value])) }}"
                                            class="{{ ($leadTrendGranularity ?? 'day') === $value ? 'button-primary' : 'button-outline' }}"
                                        >
                                            {{ $label }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="surface-panel__body">
                            @if(in_array($leadTrendGranularity, ['month', 'year'], true))
                                <div class="mb-5 h-72 rounded-2xl border border-slate-800 bg-slate-950/40 p-4">
                                    <canvas
                                        id="leadsTrendChart"
                                        data-trend='@json($leadTrendChart)'
                                        data-count-label="Leads"
                                        data-amount-label="Lead Amount"
                                    ></canvas>
                                </div>
                            @endif

                            <div class="table-shell">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Leads</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($leadTrend as $row)
                                            <tr>
                                                <td>{{ $row['date'] }}</td>
                                                <td>{{ number_format($row['total_leads']) }}</td>
                                                <td>${{ number_format($row['total_amount'], 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="empty-state">No lead trend data available.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartCanvas = document.getElementById('analyticsPieChart');

            if (typeof window.Chart === 'undefined') {
                return;
            }

            function createTrendChart(canvasId) {
                const canvas = document.getElementById(canvasId);

                if (!canvas) {
                    return;
                }

                const trend = JSON.parse(canvas.dataset.trend || '{}');
                const labels = Array.isArray(trend.labels) ? trend.labels : [];
                const countValues = Array.isArray(trend.count_values) ? trend.count_values : [];
                const amountValues = Array.isArray(trend.amount_values) ? trend.amount_values : [];

                if (labels.length === 0) {
                    return;
                }

                new window.Chart(canvas, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: canvas.dataset.countLabel || 'Count',
                                data: countValues,
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.15)',
                                yAxisID: 'yCount',
                                tension: 0.3,
                                fill: false,
                            },
                            {
                                label: canvas.dataset.amountLabel || 'Amount',
                                data: amountValues,
                                borderColor: '#22c55e',
                                backgroundColor: 'rgba(34, 197, 94, 0.15)',
                                yAxisID: 'yAmount',
                                tension: 0.3,
                                fill: false,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                ticks: { color: '#94a3b8' },
                                grid: { color: 'rgba(51, 65, 85, 0.4)' }
                            },
                            yCount: {
                                position: 'left',
                                ticks: { color: '#93c5fd' },
                                grid: { color: 'rgba(51, 65, 85, 0.4)' }
                            },
                            yAmount: {
                                position: 'right',
                                ticks: {
                                    color: '#86efac',
                                    callback: function(value) {
                                        return `$${Number(value).toLocaleString()}`;
                                    }
                                },
                                grid: { drawOnChartArea: false }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: { color: '#e2e8f0' }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        if (context.dataset.yAxisID === 'yAmount') {
                                            return `${context.dataset.label}: $${Number(context.raw || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                                        }

                                        return `${context.dataset.label}: ${Number(context.raw || 0).toLocaleString()}`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            createTrendChart('ordersTrendChart');
            createTrendChart('leadsTrendChart');

            if (!chartCanvas) {
                return;
            }

            const chartEntries = JSON.parse(chartCanvas.dataset.chart || '[]');

            if (!Array.isArray(chartEntries) || chartEntries.length === 0) {
                return;
            }

            const selectedMetric = chartCanvas.dataset.selectedMetric || 'count';
            const total = Number(chartCanvas.dataset.total || 0);
            const metricLabel = chartCanvas.dataset.metric || '';

            new window.Chart(chartCanvas, {
                type: 'pie',
                data: {
                    labels: chartEntries.map((entry) => entry.label),
                    datasets: [
                        {
                            data: chartEntries.map((entry) => entry.value),
                            backgroundColor: chartEntries.map((entry) => entry.color),
                            borderColor: '#111827',
                            borderWidth: 3,
                            hoverOffset: 10,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = Number(context.raw || 0);
                                    const percent = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';

                                    if (selectedMetric === 'amount') {
                                        return `${context.label}: $${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (${percent}%)`;
                                    }

                                    return `${context.label}: ${value.toLocaleString()} ${metricLabel.toLowerCase()} (${percent}%)`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
