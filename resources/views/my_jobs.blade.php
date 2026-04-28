<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs - Ordering Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page">
    <nav class="app-nav no-print">
        <div class="app-nav-inner">
            <a class="app-brand" href="{{ route('my.jobs') }}">Ordering Portal</a>

            <a href="{{ route('dealers.index') }}" class="button-outline">
                Exit Impersonation
            </a>
        </div>
    </nav>

    <div class="grid w-full px-4 pb-8 lg:grid-cols-[minmax(0,1.2fr)_minmax(24rem,0.8fr)] lg:px-6">
        <section class="min-h-screen border-b border-slate-800 py-6 lg:border-b-0 lg:border-r lg:pr-6">
            <div class="mb-6 flex flex-wrap gap-3">
                <a href="{{ route('my.orders') }}" class="button-outline">My Orders</a>
                <a href="{{ route('my.jobs') }}" class="button-primary">My Jobs</a>
            </div>

            <div class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-heading">Ordering Portal</p>
                        <h1 class="mt-2 text-2xl font-semibold text-white">My Jobs</h1>
                        <p class="mt-2 text-sm text-slate-400">Review the current user’s open jobs and inspect the raw response payloads.</p>
                    </div>

                    <button id="fetchAllBtn" class="button-accent">
                        Fetch All Jobs
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
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($jobs as $job)
                                    <tr>
                                        <td><span class="font-semibold text-white">{{ $job['id_with_prefix'] ?? $job['id'] ?? 'N/A' }}</span></td>
                                        <td>{{ $job['customer']['display_name'] ?? $job['customer']['name'] ?? 'N/A' }}</td>
                                        <td><span class="status-badge status-badge--info">{{ $job['status'] ?? 'N/A' }}</span></td>
                                        <td>${{ number_format($job['total'] ?? 0, 2) }}</td>
                                        <td>{{ $job['created_at'] ?? 'N/A' }}</td>
                                        <td>
                                            <a href="{{ route('orders.show', $job['container_id'] ?? $job['id']) }}" class="button-outline">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="empty-state">No jobs found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(isset($pagination) && $pagination['last_page'] > 1)
                        <nav id="paginationNav" class="mt-6">
                            <div class="pagination-list">
                                <a
                                    href="{{ route('my.jobs', ['page' => $pagination['current_page'] - 1]) }}"
                                    class="pagination-link {{ $pagination['current_page'] <= 1 ? 'pagination-link--disabled' : '' }}"
                                >
                                    Previous
                                </a>

                                @for($i = 1; $i <= $pagination['last_page']; $i++)
                                    @if($i == 1 || $i == $pagination['last_page'] || ($i >= $pagination['current_page'] - 2 && $i <= $pagination['current_page'] + 2))
                                        <a
                                            href="{{ route('my.jobs', ['page' => $i]) }}"
                                            class="pagination-link {{ $pagination['current_page'] == $i ? 'pagination-link--active' : '' }}"
                                        >
                                            {{ $i }}
                                        </a>
                                    @elseif($i == 2 || $i == $pagination['last_page'] - 1)
                                        <span class="pagination-link pagination-link--disabled">...</span>
                                    @endif
                                @endfor

                                <a
                                    href="{{ route('my.jobs', ['page' => $pagination['current_page'] + 1]) }}"
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
                    <p class="mt-2 text-sm text-slate-400">Job payloads are logged here as you browse or fetch every record.</p>
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

        function renderJobRow(job) {
            const total = parseFloat(job.total || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            const customerName = (job.customer && job.customer.display_name) || (job.customer && job.customer.name) || 'N/A';

            return `
                <tr>
                    <td><span class="font-semibold text-white">${job.id_with_prefix || job.id || 'N/A'}</span></td>
                    <td>${customerName}</td>
                    <td><span class="status-badge status-badge--info">${job.status || 'N/A'}</span></td>
                    <td>$${total}</td>
                    <td>${job.created_at || 'N/A'}</td>
                    <td>
                        <a href="/orders/${job.container_id || job.id}" class="button-outline">
                            View Details
                        </a>
                    </td>
                </tr>
            `;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const fetchAllBtn = document.getElementById('fetchAllBtn');
            const tbody = document.querySelector('tbody');
            const paginationNav = document.getElementById('paginationNav');

            if (!fetchAllBtn || !tbody) {
                return;
            }

            fetchAllBtn.addEventListener('click', function() {
                const originalText = fetchAllBtn.innerHTML;

                fetchAllBtn.disabled = true;
                fetchAllBtn.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Fetching...</span>';

                fetch('{{ route('my.jobs.all') }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.api_logs) {
                            data.api_logs.forEach(log => {
                                appendApiResponse(log.method, log.url, log.status, log.body);
                            });
                        }

                        if (data.error) {
                            alert(data.error);
                            fetchAllBtn.disabled = false;
                            fetchAllBtn.innerHTML = originalText;
                            return;
                        }

                        tbody.innerHTML = '';

                        if (data.data.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No jobs found.</td></tr>';
                        } else {
                            data.data.forEach(job => {
                                tbody.insertAdjacentHTML('beforeend', renderJobRow(job));
                            });
                        }

                        if (paginationNav) {
                            paginationNav.classList.add('hidden');
                        }

                        fetchAllBtn.innerHTML = `Fetched ${data.data.length} Jobs`;
                        fetchAllBtn.classList.remove('button-accent');
                        fetchAllBtn.classList.add('button-success');
                    })
                    .catch(err => {
                        alert('An error occurred while fetching all jobs.');
                        console.error(err);
                        fetchAllBtn.disabled = false;
                        fetchAllBtn.innerHTML = originalText;
                    });
            });
        });
    </script>
</body>
</html>
