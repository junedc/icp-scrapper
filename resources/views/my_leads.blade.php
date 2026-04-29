<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leads - Ordering Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page">
    <nav class="app-nav no-print">
        <div class="app-nav-inner">
            <a class="app-brand" href="{{ route('my.leads') }}">Ordering Portal</a>

            <a href="{{ route('dealers.index') }}" class="button-outline">
                Exit Impersonation
            </a>
        </div>
    </nav>

    <div class="grid w-full px-4 pb-8 lg:grid-cols-[minmax(0,1.2fr)_minmax(24rem,0.8fr)] lg:px-6">
        <section class="min-h-screen border-b border-slate-800 py-6 lg:border-b-0 lg:border-r lg:pr-6">
            <div class="mb-6 flex flex-wrap gap-3">
                <a href="{{ route('my.orders') }}" class="button-outline">My Orders</a>
                <a href="{{ route('my.jobs') }}" class="button-outline">My Jobs</a>
                <a href="{{ route('my.leads') }}" class="button-primary">My Leads</a>
            </div>

            <div class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-heading">Ordering Portal</p>
                        <h1 class="mt-2 text-2xl font-semibold text-white">My Leads</h1>
                        <p class="mt-2 text-sm text-slate-400">Review the current user’s active leads and inspect the raw response payloads.</p>
                    </div>

                    <button id="fetchAllBtn" class="button-accent">
                        Fetch All Leads
                    </button>
                </div>

                <div class="surface-panel__body">
                    @if(isset($error))
                        <div class="alert-error mb-5">{{ $error }}</div>
                    @endif

                    <div class="table-shell">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Lead ID</th>
                                    <th>Container</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Expiry</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($leads as $lead)
                                    <tr>
                                        <td><span class="font-semibold text-white">{{ $lead['id'] ?? 'N/A' }}</span></td>
                                        <td>{{ $lead['container_id'] ?? 'N/A' }}</td>
                                        <td><span class="status-badge status-badge--info">{{ $lead['status'] ?? 'N/A' }}</span></td>
                                        <td>${{ number_format($lead['amount'] ?? 0, 2) }}</td>
                                        <td>{{ $lead['expiry_date_time'] ?? 'N/A' }}</td>
                                        <td>
                                            @if(isset($lead['container_id']))
                                                <a href="{{ route('orders.show', $lead['container_id']) }}" class="button-outline">
                                                    View Details
                                                </a>
                                            @else
                                                <span class="text-sm text-slate-500">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="empty-state">No leads found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex flex-col items-center gap-3">
                        <p class="text-sm text-slate-400" id="leadsPaginationSummary">
                            Showing page {{ $pagination['current_page'] ?? 1 }} of {{ $pagination['last_page'] ?? 1 }}.
                        </p>

                        @if(isset($pagination) && $pagination['current_page'] < $pagination['last_page'])
                            <button
                                id="loadMoreBtn"
                                class="button-secondary"
                                data-next-page="{{ $pagination['current_page'] + 1 }}"
                                data-last-page="{{ $pagination['last_page'] }}"
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
                    <p class="mt-2 text-sm text-slate-400">Lead payloads are logged here as you browse or fetch every record.</p>
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

        function renderLeadRow(lead) {
            const amount = parseFloat(lead.amount || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            const action = lead.container_id
                ? `<a href="/orders/${lead.container_id}" class="button-outline">View Details</a>`
                : '<span class="text-sm text-slate-500">N/A</span>';

            return `
                <tr>
                    <td><span class="font-semibold text-white">${lead.id || 'N/A'}</span></td>
                    <td>${lead.container_id || 'N/A'}</td>
                    <td><span class="status-badge status-badge--info">${lead.status || 'N/A'}</span></td>
                    <td>$${amount}</td>
                    <td>${lead.expiry_date_time || 'N/A'}</td>
                    <td>${action}</td>
                </tr>
            `;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const fetchAllBtn = document.getElementById('fetchAllBtn');
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            const paginationSummary = document.getElementById('leadsPaginationSummary');
            const tbody = document.querySelector('tbody');

            if (!fetchAllBtn || !tbody) {
                return;
            }

            function updatePaginationSummary(currentPage, lastPage) {
                if (!paginationSummary) {
                    return;
                }

                paginationSummary.textContent = `Showing page ${currentPage} of ${lastPage}.`;
            }

            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    const nextPage = Number(loadMoreBtn.dataset.nextPage || 1);
                    const lastPage = Number(loadMoreBtn.dataset.lastPage || nextPage);
                    const originalText = loadMoreBtn.innerHTML;

                    loadMoreBtn.disabled = true;
                    loadMoreBtn.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Loading...</span>';

                    const loadMoreUrl = new URL('{{ route('my.leads.page') }}', window.location.origin);
                    loadMoreUrl.searchParams.set('page', String(nextPage));

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

                            data.data.forEach(lead => {
                                tbody.insertAdjacentHTML('beforeend', renderLeadRow(lead));
                            });

                            const currentPage = Number(data.pagination?.current_page || nextPage);
                            const updatedLastPage = Number(data.pagination?.last_page || lastPage);

                            updatePaginationSummary(currentPage, updatedLastPage);

                            if (currentPage >= updatedLastPage) {
                                loadMoreBtn.remove();
                                return;
                            }

                            loadMoreBtn.dataset.nextPage = String(currentPage + 1);
                            loadMoreBtn.dataset.lastPage = String(updatedLastPage);
                            loadMoreBtn.disabled = false;
                            loadMoreBtn.innerHTML = originalText;
                        })
                        .catch(error => {
                            alert('An error occurred while loading more leads.');
                            console.error(error);
                            loadMoreBtn.disabled = false;
                            loadMoreBtn.innerHTML = originalText;
                        });
                });
            }

            fetchAllBtn.addEventListener('click', async function() {
                fetchAllBtn.disabled = true;
                const originalHtml = fetchAllBtn.innerHTML;
                fetchAllBtn.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Fetching...</span>';

                try {
                    const response = await fetch('{{ route('my.leads.all') }}');
                    const result = await response.json();

                    if (result.api_logs) {
                        result.api_logs.forEach(log => {
                            appendApiResponse(log.method, log.url, log.status, log.body);
                        });
                    }

                    if (! response.ok) {
                        throw new Error(result.error || 'Unable to fetch leads.');
                    }

                    if (Array.isArray(result.data)) {
                        if (result.data.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No leads found.</td></tr>';
                        } else {
                            tbody.innerHTML = result.data.map(renderLeadRow).join('');
                        }
                    }

                    updatePaginationSummary(result.last_page || 1, result.last_page || 1);
                } catch (error) {
                    alert('An error occurred while fetching all leads.');
                    console.error(error);
                } finally {
                    fetchAllBtn.disabled = false;
                    fetchAllBtn.innerHTML = originalHtml;
                }
            });
        });
    </script>
</body>
</html>
