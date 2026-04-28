<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs - Ordering Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Ordering Portal</a>
            <div class="navbar-nav ms-auto">
                <a href="{{ route('dealers.index') }}" class="btn btn-outline-light btn-sm">Exit Impersonation</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="mb-4 d-flex gap-2">
                    <a href="{{ route('my.orders') }}" class="btn btn-outline-primary">My Orders</a>
                    <a href="{{ route('my.jobs') }}" class="btn btn-primary">My Jobs</a>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary">My Jobs</h5>
                        <button id="fetchAllBtn" class="btn btn-sm btn-info text-white">
                            Fetch All Jobs
                        </button>
                    </div>
                    <div class="card-body">
                        @if(isset($error))
                            <div class="alert alert-danger">{{ $error }}</div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
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
                                            <td>
                                                <span class="fw-bold">{{ $job['id_with_prefix'] ?? $job['id'] ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                {{ $job['customer']['display_name'] ?? $job['customer']['name'] ?? 'N/A' }}
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    {{ $job['status'] ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>${{ number_format($job['total'] ?? 0, 2) }}</td>
                                            <td>{{ $job['created_at'] ?? 'N/A' }}</td>
                                            <td>
                                                <a href="{{ route('orders.show', $job['container_id'] ?? $job['id']) }}" class="btn btn-sm btn-outline-primary">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4">No jobs found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if(isset($pagination) && $pagination['last_page'] > 1)
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item {{ $pagination['current_page'] <= 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ route('my.jobs', ['page' => $pagination['current_page'] - 1]) }}">Previous</a>
                                    </li>

                                    @for($i = 1; $i <= $pagination['last_page']; $i++)
                                        @if($i == 1 || $i == $pagination['last_page'] || ($i >= $pagination['current_page'] - 2 && $i <= $pagination['current_page'] + 2))
                                            <li class="page-item {{ $pagination['current_page'] == $i ? 'active' : '' }}">
                                                <a class="page-link" href="{{ route('my.jobs', ['page' => $i]) }}">{{ $i }}</a>
                                            </li>
                                        @elseif($i == 2 || $i == $pagination['last_page'] - 1)
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        @endif
                                    @endfor

                                    <li class="page-item {{ $pagination['current_page'] >= $pagination['last_page'] ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ route('my.jobs', ['page' => $pagination['current_page'] + 1]) }}">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5 mb-5 no-print" id="apiResponseLogsContainer">
        <hr>
        <h5 class="text-muted">API Response Logs</h5>
        <div id="apiResponseLogs">
            @if(isset($api_logs) && count($api_logs) > 0)
                @foreach($api_logs as $log)
                    <div class="mb-3">
                        <div class="small text-muted mb-1">{{ $log['method'] }} {{ $log['url'] }} (Status: {{ $log['status'] }})</div>
                        <textarea class="form-control bg-dark text-success font-monospace" rows="10" readonly>{{ json_encode($log['body'], JSON_PRETTY_PRINT) }}</textarea>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <script>
        function appendApiResponse(method, url, status, body) {
            const container = document.getElementById('apiResponseLogsContainer');
            if (container) container.classList.remove('d-none');

            const logDiv = document.getElementById('apiResponseLogs');
            const logEntry = document.createElement('div');
            logEntry.className = 'mb-3';

            const prettyBody = typeof body === 'object' ? JSON.stringify(body, null, 4) : body;

            logEntry.innerHTML = `
                <div class="small text-muted mb-1">${method} ${url} (Status: ${status})</div>
                <textarea class="form-control bg-dark text-success font-monospace" rows="10" readonly>${prettyBody}</textarea>
            `;
            logDiv.appendChild(logEntry);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const fetchAllBtn = document.getElementById('fetchAllBtn');
            const tbody = document.querySelector('tbody');
            const paginationNav = document.querySelector('nav.mt-4');

            if (fetchAllBtn) {
                fetchAllBtn.addEventListener('click', function() {
                    const originalText = fetchAllBtn.innerHTML;
                    fetchAllBtn.disabled = true;
                    fetchAllBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Fetching...';

                    fetch('{{ route('my.jobs.all') }}')
                        .then(response => response.json())
                        .then(data => {
                            // Log external API calls if available
                            if (data.api_logs) {
                                data.api_logs.forEach(log => {
                                    appendApiResponse(log.method, log.url, log.status, log.body);
                                });
                            }

                            if (data.error) {
                                alert(data.error);
                            } else {
                                // Clear current table
                                tbody.innerHTML = '';

                                if (data.data.length === 0) {
                                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No jobs found.</td></tr>';
                                } else {
                                    data.data.forEach(job => {
                                        const total = parseFloat(job.total || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                        const customerName = (job.customer && job.customer.display_name) || (job.customer && job.customer.name) || 'N/A';

                                        const row = `
                                            <tr>
                                                <td><span class="fw-bold">${job.id_with_prefix || job.id || 'N/A'}</span></td>
                                                <td>${customerName}</td>
                                                <td><span class="badge bg-info">${job.status || 'N/A'}</span></td>
                                                <td>$${total}</td>
                                                <td>${job.created_at || 'N/A'}</td>
                                                <td>
                                                    <a href="/orders/${job.container_id || job.id}" class="btn btn-sm btn-outline-primary">
                                                        View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        `;
                                        tbody.insertAdjacentHTML('beforeend', row);
                                    });
                                }

                                // Hide pagination since we're showing everything
                                if (paginationNav) {
                                    paginationNav.classList.add('d-none');
                                }

                                fetchAllBtn.innerHTML = `Fetched ${data.data.length} Jobs`;
                                fetchAllBtn.classList.remove('btn-info');
                                fetchAllBtn.classList.add('btn-success');

                                // Log API response
                                // appendApiResponse('GET', '{{ route('my.jobs.all') }}', 200, data);
                            }
                        })
                        .catch(err => {
                            alert('An error occurred while fetching all jobs.');
                            console.error(err);
                            fetchAllBtn.disabled = false;
                            fetchAllBtn.innerHTML = originalText;
                        });
                });
            }
        });
    </script>
</body>
</html>
