<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Scraper</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page">
    <nav class="app-nav no-print">
        <div class="app-nav-inner">
            <a class="app-brand" href="{{ route('dealers.index') }}">Scraper</a>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="button-outline">
                    Logout
                </button>
            </form>
        </div>
    </nav>

    <div class="grid w-full px-4 pb-8 lg:grid-cols-[minmax(0,1.2fr)_minmax(24rem,0.8fr)] lg:px-6">
        <section class="min-h-screen border-b border-slate-800 py-6 lg:border-b-0 lg:border-r lg:pr-6">
            <div class="mb-6 flex items-center justify-between">
                <a href="{{ route('dealers.index') }}" class="button-secondary">
                    &larr; Back to Dealers
                </a>
            </div>

            @if(isset($statistics) && count($statistics) > 0)
                <div class="mb-6 grid gap-3 sm:grid-cols-2">
                    @foreach($statistics as $status => $count)
                        <div class="metric-card">
                            <p class="metric-label">{{ $status }}</p>
                            <p class="metric-value">{{ $count }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-heading">Dealer Orders</p>
                        <h1 class="mt-2 text-2xl font-semibold text-white">Orders for Dealer #{{ $id }}</h1>
                    </div>
                </div>

                <div class="surface-panel__body">
                    @if(isset($error))
                        <div class="alert-error mb-5">{{ $error }}</div>
                    @endif

                    <div class="table-shell">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Container ID</th>
                                    <th>Order Number</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(is_iterable($orders))
                                    @forelse($orders as $order)
                                        @php
                                            $statusClass = in_array(strtolower($order['status'] ?? ''), ['completed', 'in production'], true)
                                                ? 'status-badge status-badge--success'
                                                : 'status-badge status-badge--info';
                                        @endphp
                                        <tr>
                                            <td>{{ $order['id'] ?? $order['id_with_prefix'] ?? 'N/A' }}</td>
                                            <td>
                                                <a href="{{ route('orders.show', $order['container_id'] ?? $order['container']['id'] ?? 0) }}" class="data-link">
                                                    {{ $order['container_id'] ?? $order['container']['id'] ?? 'N/A' }}
                                                </a>
                                            </td>
                                            <td>{{ $order['order_number'] ?? $order['number'] ?? $order['dealer_reference'] ?? 'N/A' }}</td>
                                            <td>
                                                <span class="{{ $statusClass }}">
                                                    {{ $order['status'] ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>{{ $order['total'] ?? 'N/A' }}</td>
                                            <td>{{ $order['order_date'] ?? $order['created_date'] ?? (isset($order['created_at']) ? date('Y-m-d H:i', strtotime($order['created_at'])) : 'N/A') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="empty-state">No orders found for this dealer.</td>
                                        </tr>
                                    @endforelse
                                @else
                                    <tr>
                                        <td colspan="6" class="empty-state">Unable to load orders.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if(isset($pagination) && $pagination['last_page'] > 1)
                        <nav class="mt-6">
                            <div class="pagination-list">
                                <a
                                    href="{{ route('dealers.orders', array_merge(['id' => $id], request()->query(), ['page' => $pagination['current_page'] - 1])) }}"
                                    class="pagination-link {{ $pagination['current_page'] <= 1 ? 'pagination-link--disabled' : '' }}"
                                >
                                    Previous
                                </a>

                                @for($i = 1; $i <= $pagination['last_page']; $i++)
                                    @if($i == 1 || $i == $pagination['last_page'] || ($i >= $pagination['current_page'] - 2 && $i <= $pagination['current_page'] + 2))
                                        <a
                                            href="{{ route('dealers.orders', array_merge(['id' => $id], request()->query(), ['page' => $i])) }}"
                                            class="pagination-link {{ $pagination['current_page'] == $i ? 'pagination-link--active' : '' }}"
                                        >
                                            {{ $i }}
                                        </a>
                                    @elseif($i == 2 || $i == $pagination['last_page'] - 1)
                                        <span class="pagination-link pagination-link--disabled">...</span>
                                    @endif
                                @endfor

                                <a
                                    href="{{ route('dealers.orders', array_merge(['id' => $id], request()->query(), ['page' => $pagination['current_page'] + 1])) }}"
                                    class="pagination-link {{ $pagination['current_page'] >= $pagination['last_page'] ? 'pagination-link--disabled' : '' }}"
                                >
                                    Next
                                </a>
                            </div>
                        </nav>
                    @endif
                </div>
            </div>
        </section>

        <aside class="min-h-screen bg-slate-900/60 no-print" id="apiResponseLogsContainer">
            <div class="log-panel lg:sticky lg:top-0">
                <div class="mb-6">
                    <p class="section-heading">Diagnostics</p>
                    <h2 class="mt-3 text-2xl font-semibold text-white">API Response Logs</h2>
                    <p class="mt-2 text-sm text-slate-400">Each dealer orders request is captured here.</p>
                </div>

                <div id="apiResponseLogs" class="space-y-4">
                    @if(isset($api_logs) && count($api_logs) > 0)
                        @foreach($api_logs as $log)
                            <div class="log-entry">
                                <div class="log-meta">{{ $log['method'] }} {{ $log['url'] }} (Status: {{ $log['status'] }})</div>
                                <textarea class="log-textarea" rows="18" readonly>{{ json_encode($log['body'], JSON_PRETTY_PRINT) }}</textarea>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state rounded-2xl border border-dashed border-slate-800 bg-slate-950/40">
                            No API responses yet.
                        </div>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</body>
</html>
