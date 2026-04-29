<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Analytics - Ordering Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page">
    <nav class="app-nav no-print">
        <div class="app-nav-inner">
            <a class="app-brand" href="{{ route('analytics.orders') }}">Ordering Portal</a>

            <a href="{{ route('dealers.index') }}" class="button-outline">
                Exit Impersonation
            </a>
        </div>
    </nav>

    @php
        $chartEntries = $chartConfig['entries'] ?? [];
        $chartTotal = (float) ($chartConfig['total'] ?? 0);
        $chartCenter = 56;
        $chartRadius = 36;
        $chartCircumference = 2 * pi() * $chartRadius;
        $chartOffset = 0.0;
    @endphp

    <div class="w-full px-4 pb-6 lg:px-6">
        <section class="py-5">
            <div class="mb-4 flex flex-wrap gap-3">
                <a href="{{ route('my.orders') }}" class="button-outline">My Orders</a>
                <a href="{{ route('my.jobs') }}" class="button-outline">My Jobs</a>
                <a href="{{ route('my.leads') }}" class="button-outline">My Leads</a>
                <a href="{{ route('analytics.orders') }}" class="button-primary">Analytics</a>
            </div>

            <div class="space-y-4">
                <div class="surface-panel">
                    <div class="surface-panel__header">
                        <div>
                            <p class="section-heading">Analytics</p>
                            <h1 class="mt-1 text-xl font-semibold text-white">Orders And Leads Dashboard</h1>
                            <p class="mt-1 text-sm text-slate-400">Compact staged analytics across dealer orders, payments, and leads.</p>
                            @if($latestSyncAt)
                                <p class="mt-2 text-xs uppercase tracking-[0.16em] text-slate-500">
                                    Latest sync {{ \Illuminate\Support\Carbon::parse($latestSyncAt)->format('Y-m-d H:i') }}
                                </p>
                            @endif
                        </div>

                        <form action="{{ route('analytics.orders') }}" method="GET" class="grid w-full gap-3 md:grid-cols-3 xl:grid-cols-7">
                            <div>
                                <label class="label-text" for="dealer_scope">Dealer</label>
                                <select id="dealer_scope" name="dealer_scope" class="input-field input-field--sm">
                                    <option value="">All dealers</option>
                                    @foreach($availableScopes as $scope)
                                        <option value="{{ $scope }}" @selected(($filters['dealer_scope'] ?? null) === $scope)>{{ $scope }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label-text" for="status">Order Status</label>
                                <select id="status" name="status" class="input-field input-field--sm">
                                    <option value="">All statuses</option>
                                    @foreach($availableOrderStatuses as $status)
                                        <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label-text" for="date_from">From</label>
                                <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="input-field input-field--sm">
                            </div>
                            <div>
                                <label class="label-text" for="date_to">To</label>
                                <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="input-field input-field--sm">
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
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="label-text" for="chart_limit">Entries</label>
                                    <input id="chart_limit" type="number" min="2" max="12" name="chart_limit" value="{{ $filters['chart_limit'] ?? 5 }}" class="input-field input-field--sm">
                                </div>
                                <div class="flex items-end gap-2">
                                    <button type="submit" class="button-primary w-full">Apply</button>
                                    <a href="{{ route('analytics.orders') }}" class="button-secondary w-full">Reset</a>
                                </div>
                            </div>
                        </form>
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

                <div class="grid gap-4 xl:grid-cols-[minmax(0,0.88fr)_minmax(0,1.12fr)]">
                    <div class="surface-panel">
                        <div class="surface-panel__header">
                            <div>
                                <p class="section-heading">Pie Chart</p>
                                <h2 class="mt-1 text-lg font-semibold text-white">{{ $chartConfig['title'] }}</h2>
                                <p class="mt-1 text-sm text-slate-400">Metric: {{ $chartConfig['metric_label'] }}</p>
                            </div>
                        </div>
                        <div class="surface-panel__body">
                            <div class="grid gap-4 lg:grid-cols-[13rem_minmax(0,1fr)] lg:items-center">
                                <div class="flex items-center justify-center">
                                    <div class="relative">
                                        <svg viewBox="0 0 112 112" class="h-52 w-52 -rotate-90">
                                            <circle cx="{{ $chartCenter }}" cy="{{ $chartCenter }}" r="{{ $chartRadius }}" fill="none" stroke="#1f2937" stroke-width="18"></circle>
                                            @forelse($chartEntries as $entry)
                                                @php
                                                    $sliceLength = $chartTotal > 0 ? ($entry['value'] / $chartTotal) * $chartCircumference : 0;
                                                @endphp
                                                <circle
                                                    cx="{{ $chartCenter }}"
                                                    cy="{{ $chartCenter }}"
                                                    r="{{ $chartRadius }}"
                                                    fill="none"
                                                    stroke="{{ $entry['color'] }}"
                                                    stroke-width="18"
                                                    stroke-linecap="butt"
                                                    stroke-dasharray="{{ $sliceLength }} {{ max($chartCircumference - $sliceLength, 0) }}"
                                                    stroke-dashoffset="{{ -$chartOffset }}"
                                                ></circle>
                                                @php
                                                    $chartOffset += $sliceLength;
                                                @endphp
                                            @empty
                                            @endforelse
                                        </svg>
                                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                                            <span class="text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $chartConfig['metric_label'] }}</span>
                                            <span class="mt-1 text-xl font-semibold text-white">
                                                @if(($chartConfig['selected_metric'] ?? 'count') === 'amount')
                                                    ${{ number_format($chartConfig['total'], 2) }}
                                                @else
                                                    {{ number_format($chartConfig['total']) }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>

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

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="surface-panel">
                            <div class="surface-panel__header">
                                <div>
                                    <p class="section-heading">Orders</p>
                                    <h2 class="mt-1 text-lg font-semibold text-white">Status Breakdown</h2>
                                </div>
                            </div>
                            <div class="surface-panel__body space-y-2">
                                @forelse($statusBreakdown as $row)
                                    @php
                                        $maxStatusOrders = max(array_column($statusBreakdown, 'total_orders')) ?: 1;
                                        $statusWidth = max(6, (int) round(($row['total_orders'] / $maxStatusOrders) * 100));
                                    @endphp
                                    <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2.5">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-sm font-medium text-slate-100">{{ $row['status'] }}</span>
                                            <span class="text-xs uppercase tracking-[0.16em] text-slate-500">{{ number_format($row['total_orders']) }}</span>
                                        </div>
                                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-800">
                                            <div class="h-full rounded-full bg-sky-400" style="width: {{ $statusWidth }}%"></div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty-state rounded-xl border border-dashed border-slate-800 bg-slate-950/40">No order snapshots match the current filters.</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="surface-panel">
                            <div class="surface-panel__header">
                                <div>
                                    <p class="section-heading">Payments</p>
                                    <h2 class="mt-1 text-lg font-semibold text-white">Payment Breakdown</h2>
                                </div>
                            </div>
                            <div class="surface-panel__body space-y-2">
                                @forelse($paymentBreakdown as $row)
                                    <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2.5">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-sm font-medium text-slate-100">{{ $row['payment_status'] }}</span>
                                            <span class="text-xs uppercase tracking-[0.16em] text-slate-500">{{ number_format($row['total_orders']) }}</span>
                                        </div>
                                        <div class="mt-1 text-sm text-slate-400">${{ number_format($row['total_value'], 2) }}</div>
                                    </div>
                                @empty
                                    <div class="empty-state rounded-xl border border-dashed border-slate-800 bg-slate-950/40">No payment data captured yet.</div>
                                @endforelse
                            </div>
                        </div>
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
                            <div>
                                <p class="section-heading">Trend</p>
                                <h2 class="mt-1 text-lg font-semibold text-white">Orders By Day</h2>
                            </div>
                        </div>
                        <div class="surface-panel__body">
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
                            <div>
                                <p class="section-heading">Trend</p>
                                <h2 class="mt-1 text-lg font-semibold text-white">Leads By Day</h2>
                            </div>
                        </div>
                        <div class="surface-panel__body">
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
</body>
</html>
