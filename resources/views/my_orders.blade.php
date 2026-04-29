<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Ordering Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page">
    <nav class="app-nav no-print">
        <div class="app-nav-inner">
            <a class="app-brand" href="{{ route('my.orders') }}">Ordering Portal</a>

            <a href="{{ route('dealers.index') }}" class="button-outline">
                Exit Impersonation
            </a>
        </div>
    </nav>

    <div class="grid w-full px-4 pb-8 lg:grid-cols-[minmax(0,1.2fr)_minmax(24rem,0.8fr)] lg:px-6">
        <section class="min-h-screen border-b border-slate-800 py-6 lg:border-b-0 lg:border-r lg:pr-6">
            <div class="mb-6 flex flex-wrap gap-3">
                <a href="{{ route('my.orders') }}" class="button-primary">My Orders</a>
                <a href="{{ route('my.leads') }}" class="button-outline">My Leads</a>
                <a href="{{ route('analytics.orders') }}" class="button-outline">Analytics</a>
            </div>

            <div class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-heading">Ordering Portal</p>
                        <h1 class="mt-2 text-2xl font-semibold text-white">My Orders</h1>
                        <p class="mt-2 text-sm text-slate-400">Review the current user’s orders by status and inspect the matching API payloads.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach($availableStatuses as $status)
                                <a
                                    href="{{ route('my.orders', ['status' => $status, 'create_only' => ($createOnly ?? true) ? 1 : 0]) }}"
                                    class="{{ $selectedStatus === $status ? 'button-primary' : 'button-outline' }}"
                                >
                                    {{ $status }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-4">
                        <label class="flex items-center gap-3 text-sm text-slate-300">
                            <input
                                id="createOnlyToggle"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-600 bg-slate-950 text-sky-400 focus:ring-sky-400/30"
                                @checked($createOnly ?? true)
                            >
                            <span>
                                <span class="font-semibold text-slate-100">Create Only</span>
                                <span class="mt-1 block text-xs uppercase tracking-[0.16em] text-slate-500">
                                    Skip updates to existing analytics rows
                                </span>
                            </span>
                        </label>

                        <div class="flex flex-wrap justify-end gap-3">
                            <button id="fetchCurrentBtn" class="button-secondary">
                                Fetch Current Data
                            </button>

                            <button id="fetchAllBtn" class="button-accent">
                                Fetch All Orders
                            </button>
                        </div>
                    </div>
                </div>

                <div class="surface-panel__body">
                    @if(isset($error))
                        <div class="alert-error mb-5">{{ $error }}</div>
                    @endif

                    <div class="table-shell table-shell--scroll-y">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Status</th>
                                    <th>Reference</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    @php
                                        $status = strtolower($order['status'] ?? '');
                                        $badgeClass = 'status-badge status-badge--neutral';

                                        if ($status === 'draft') {
                                            $badgeClass = 'status-badge status-badge--warning';
                                        } elseif (in_array($status, ['completed', 'in production', 'submitted'], true)) {
                                            $badgeClass = 'status-badge status-badge--success';
                                        }
                                    @endphp
                                    <tr>
                                        <td><span class="font-semibold text-white">{{ $order['id'] ?? 'N/A' }}</span></td>
                                        <td><span class="{{ $badgeClass }}">{{ $order['status'] ?? 'N/A' }}</span></td>
                                        <td>{{ $order['dealer_reference'] ?? 'N/A' }}</td>
                                        <td>${{ number_format($order['total'] ?? 0, 2) }}</td>
                                        <td>{{ $order['order_date'] ?? 'N/A' }}</td>
                                        <td>
                                            <a href="{{ route('my.orders.specification', $order['id'] ?? $order['container_id']) }}" class="button-outline">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="empty-state">No orders found for {{ $selectedStatus }}.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex flex-col items-center gap-3">
                        <p class="text-sm text-slate-400" id="ordersPaginationSummary">
                            Showing page {{ $pagination['current_page'] ?? 1 }} of {{ $pagination['last_page'] ?? 1 }}
                            for <span class="font-semibold text-slate-200">{{ $selectedStatus }}</span>.
                        </p>

                        @if(isset($pagination) && $pagination['current_page'] < $pagination['last_page'])
                            <button
                                id="loadMoreBtn"
                                class="button-secondary"
                                data-next-page="{{ $pagination['current_page'] + 1 }}"
                                data-last-page="{{ $pagination['last_page'] }}"
                                data-status="{{ $selectedStatus }}"
                            >
                                Load More
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <aside class="min-h-screen bg-slate-900/60 no-print" id="apiResponseLogsContainer">
            <div class="log-panel lg:sticky lg:top-0">
                <div class="mb-6">
                    <p class="section-heading">Diagnostics</p>
                    <h2 class="mt-3 text-2xl font-semibold text-white">API Response Logs</h2>
                    <p class="mt-2 text-sm text-slate-400">Ordering portal responses are streamed here as you browse or fetch all records.</p>
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

    <script>
        function appendApiResponse(method, url, status, body) {
            const logDiv = document.getElementById('apiResponseLogs');

            if (!logDiv) {
                return;
            }

            const prettyBody = typeof body === 'object' ? JSON.stringify(body, null, 4) : String(body ?? '');
            const logEntry = document.createElement('div');
            const logMeta = document.createElement('div');
            const logTextarea = document.createElement('textarea');

            logEntry.className = 'log-entry';
            logMeta.className = 'log-meta';
            logMeta.textContent = `${method} ${url} (Status: ${status})`;

            logTextarea.className = 'log-textarea';
            logTextarea.rows = 18;
            logTextarea.readOnly = true;
            logTextarea.value = prettyBody;

            logEntry.appendChild(logMeta);
            logEntry.appendChild(logTextarea);
            logDiv.appendChild(logEntry);
        }

        function getOrderStatusClass(statusValue) {
            const status = String(statusValue || '').toLowerCase();

            if (status === 'draft') {
                return 'status-badge status-badge--warning';
            }

            if (['completed', 'in production', 'submitted'].includes(status)) {
                return 'status-badge status-badge--success';
            }

            return 'status-badge status-badge--neutral';
        }

        function renderOrderRow(order) {
            const total = parseFloat(order.total || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            return `
                <tr>
                    <td><span class="font-semibold text-white">${order.id || 'N/A'}</span></td>
                    <td><span class="${getOrderStatusClass(order.status)}">${order.status || 'N/A'}</span></td>
                    <td>${order.dealer_reference || 'N/A'}</td>
                    <td>$${total}</td>
                    <td>${order.order_date || 'N/A'}</td>
                    <td>
                        <a href="/my-orders/${order.id || order.container_id}/specification" class="button-outline">
                            View Details
                        </a>
                    </td>
                </tr>
            `;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const fetchAllBtn = document.getElementById('fetchAllBtn');
            const fetchCurrentBtn = document.getElementById('fetchCurrentBtn');
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            const paginationSummary = document.getElementById('ordersPaginationSummary');
            const createOnlyToggle = document.getElementById('createOnlyToggle');
            const tbody = document.querySelector('tbody');
            const selectedStatus = @json($selectedStatus);
            const createOnly = @json($createOnly ?? true);
            let fetchAllPollTimer = null;

            if (!fetchAllBtn || !tbody) {
                return;
            }

            function setRows(data, emptyMessage = 'No orders found.') {
                tbody.innerHTML = '';

                if (!Array.isArray(data) || data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="empty-state">${emptyMessage}</td></tr>`;
                    return;
                }

                data.forEach(order => {
                    tbody.insertAdjacentHTML('beforeend', renderOrderRow(order));
                });
            }

            function stopFetchAllPolling() {
                if (fetchAllPollTimer) {
                    window.clearTimeout(fetchAllPollTimer);
                    fetchAllPollTimer = null;
                }
            }

            if (createOnlyToggle) {
                createOnlyToggle.addEventListener('change', function() {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('create_only', createOnlyToggle.checked ? '1' : '0');

                    window.location.href = currentUrl.toString();
                });
            }

            function updatePaginationSummary(currentPage, lastPage, status) {
                if (!paginationSummary) {
                    return;
                }

                paginationSummary.innerHTML = `Showing page ${currentPage} of ${lastPage} for <span class="font-semibold text-slate-200">${status}</span>.`;
            }

            function fetchCurrentSnapshotData(updateButton = false) {
                const fetchCurrentUrl = new URL('{{ route('my.orders.current') }}', window.location.origin);
                fetchCurrentUrl.searchParams.set('status', selectedStatus);

                return fetch(fetchCurrentUrl.toString())
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }

                        setRows(data.data, `No local orders found for ${data.selected_status || selectedStatus}.`);

                        if (loadMoreBtn) {
                            loadMoreBtn.remove();
                        }

                        updatePaginationSummary(1, 1, `${data.selected_status || selectedStatus} (Local)`);

                        if (updateButton && fetchCurrentBtn) {
                            fetchCurrentBtn.innerHTML = `Loaded ${data.data.length} Local Records`;
                            fetchCurrentBtn.classList.remove('button-secondary');
                            fetchCurrentBtn.classList.add('button-success');
                        }

                        return data;
                    });
            }

            if (fetchCurrentBtn) {
                fetchCurrentBtn.addEventListener('click', function() {
                    const originalText = fetchCurrentBtn.innerHTML;

                    fetchCurrentBtn.disabled = true;
                    fetchCurrentBtn.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Loading...</span>';

                    fetchCurrentSnapshotData(true)
                        .catch(err => {
                            alert('An error occurred while loading current local data.');
                            console.error(err);
                            fetchCurrentBtn.disabled = false;
                            fetchCurrentBtn.innerHTML = originalText;
                        });
                });
            }

            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    const nextPage = Number(loadMoreBtn.dataset.nextPage || 1);
                    const lastPage = Number(loadMoreBtn.dataset.lastPage || nextPage);
                    const status = loadMoreBtn.dataset.status || selectedStatus;
                    const originalText = loadMoreBtn.innerHTML;

                    loadMoreBtn.disabled = true;
                    loadMoreBtn.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Loading...</span>';

                    const loadMoreUrl = new URL('{{ route('my.orders.page') }}', window.location.origin);
                    loadMoreUrl.searchParams.set('page', String(nextPage));
                    loadMoreUrl.searchParams.set('status', status);
                    loadMoreUrl.searchParams.set('create_only', createOnly ? '1' : '0');

                    fetch(loadMoreUrl.toString())
                        .then(response => response.json())
                        .then(data => {
                            if (data.api_logs) {
                                data.api_logs.forEach(log => {
                                    appendApiResponse(log.method, log.url, log.status, log.body);
                                });
                            }

                            if (data.error) {
                                alert(data.error);
                                loadMoreBtn.disabled = false;
                                loadMoreBtn.innerHTML = originalText;
                                return;
                            }

                            data.data.forEach(order => {
                                tbody.insertAdjacentHTML('beforeend', renderOrderRow(order));
                            });

                            const currentPage = Number(data.pagination?.current_page || nextPage);
                            const updatedLastPage = Number(data.pagination?.last_page || lastPage);

                            updatePaginationSummary(currentPage, updatedLastPage, data.selected_status || status);

                            if (currentPage >= updatedLastPage) {
                                loadMoreBtn.remove();
                                return;
                            }

                            loadMoreBtn.dataset.nextPage = String(currentPage + 1);
                            loadMoreBtn.dataset.lastPage = String(updatedLastPage);
                            loadMoreBtn.dataset.status = data.selected_status || status;
                            loadMoreBtn.disabled = false;
                            loadMoreBtn.innerHTML = originalText;
                        })
                        .catch(err => {
                            alert('An error occurred while loading more orders.');
                            console.error(err);
                            loadMoreBtn.disabled = false;
                            loadMoreBtn.innerHTML = originalText;
                        });
                });
            }

            fetchAllBtn.addEventListener('click', function() {
                const originalText = fetchAllBtn.innerHTML;

                fetchAllBtn.disabled = true;
                fetchAllBtn.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Queueing...</span>';

                const fetchAllUrl = new URL('{{ route('my.orders.all') }}', window.location.origin);
                fetchAllUrl.searchParams.set('create_only', createOnly ? '1' : '0');

                fetch(fetchAllUrl.toString())
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                            fetchAllBtn.disabled = false;
                            fetchAllBtn.innerHTML = originalText;
                            return;
                        }

                        const syncId = Number(data.sync_id || 0);

                        if (!syncId) {
                            alert('Unable to start queued order sync.');
                            fetchAllBtn.disabled = false;
                            fetchAllBtn.innerHTML = originalText;
                            return;
                        }

                        const pollSyncStatus = () => {
                            const statusUrl = new URL(`/my-orders/syncs/${syncId}`, window.location.origin);

                            fetch(statusUrl.toString())
                                .then(response => response.json())
                                .then(sync => {
                                    if (sync.status === 'failed') {
                                        stopFetchAllPolling();
                                        fetchAllBtn.disabled = false;
                                        fetchAllBtn.innerHTML = 'Fetch All Orders';
                                        alert(sync.error_message || 'Queued order sync failed.');
                                        return;
                                    }

                                    if (sync.status === 'completed') {
                                        stopFetchAllPolling();
                                        fetchAllBtn.innerHTML = `Synced ${sync.total_records || 0} Orders`;
                                        fetchAllBtn.classList.remove('button-accent');
                                        fetchAllBtn.classList.add('button-success');
                                        fetchCurrentSnapshotData().catch(console.error);
                                        return;
                                    }

                                    fetchAllBtn.innerHTML = `<span class="spinner" aria-hidden="true"></span><span>Syncing ${sync.current_status || 'orders'} page ${sync.current_page || 1}${sync.last_page ? ' of ' + sync.last_page : ''}</span>`;
                                    fetchAllPollTimer = window.setTimeout(pollSyncStatus, 2000);
                                })
                                .catch(err => {
                                    stopFetchAllPolling();
                                    console.error(err);
                                    fetchAllBtn.disabled = false;
                                    fetchAllBtn.innerHTML = originalText;
                                    alert('Unable to read queued order sync status.');
                                });
                        };

                        stopFetchAllPolling();
                        fetchAllBtn.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Queued...</span>';
                        fetchAllPollTimer = window.setTimeout(pollSyncStatus, 1200);
                    })
                    .catch(err => {
                        alert('An error occurred while queueing all orders.');
                        console.error(err);
                        fetchAllBtn.disabled = false;
                        fetchAllBtn.innerHTML = originalText;
                    });
            });
        });
    </script>
</body>
</html>
