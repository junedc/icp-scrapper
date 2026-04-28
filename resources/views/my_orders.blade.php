<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Ordering Portal</title>
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
                    <a href="{{ route('my.orders') }}" class="btn btn-primary">My Orders</a>
                    <a href="{{ route('my.jobs') }}" class="btn btn-outline-primary">My Jobs</a>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-primary">My Orders</h5>
                        <button id="fetchAllBtn" class="btn btn-sm btn-info text-white">
                            Fetch All Orders
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
                                        <th>Status</th>
                                        <th>Reference</th>
                                        <th>Total</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($orders as $order)
                                        <tr>
                                            <td>
                                                <span class="fw-bold">{{ $order['id'] ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $status = strtolower($order['status'] ?? '');
                                                    $badgeClass = 'bg-secondary';
                                                    if ($status === 'draft') $badgeClass = 'bg-warning text-dark';
                                                    if (in_array($status, ['completed', 'in production', 'submitted'])) $badgeClass = 'bg-success';
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">
                                                    {{ $order['status'] ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>{{ $order['dealer_reference'] ?? 'N/A' }}</td>
                                            <td>${{ number_format($order['total'] ?? 0, 2) }}</td>
                                            <td>{{ $order['order_date'] ?? 'N/A' }}</td>
                                            <td>
                                                <a href="{{ route('orders.show', $order['container_id'] ?? $order['id']) }}" class="btn btn-sm btn-outline-primary">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4">No orders found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if(isset($pagination) && $pagination['last_page'] > 1)
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item {{ $pagination['current_page'] <= 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ route('my.orders', ['page' => $pagination['current_page'] - 1]) }}">Previous</a>
                                    </li>

                                    @for($i = 1; $i <= $pagination['last_page']; $i++)
                                        @if($i == 1 || $i == $pagination['last_page'] || ($i >= $pagination['current_page'] - 2 && $i <= $pagination['current_page'] + 2))
                                            <li class="page-item {{ $pagination['current_page'] == $i ? 'active' : '' }}">
                                                <a class="page-link" href="{{ route('my.orders', ['page' => $i]) }}">{{ $i }}</a>
                                            </li>
                                        @elseif($i == 2 || $i == $pagination['last_page'] - 1)
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        @endif
                                    @endfor

                                    <li class="page-item {{ $pagination['current_page'] >= $pagination['last_page'] ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ route('my.orders', ['page' => $pagination['current_page'] + 1]) }}">Next</a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

                    fetch('{{ route('my.orders.all') }}')
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
                                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">No orders found.</td></tr>';
                                } else {
                                    data.data.forEach(order => {
                                        const total = parseFloat(order.total || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                        const status = (order.status || '').toLowerCase();
                                        let badgeClass = 'bg-secondary';
                                        if (status === 'draft') badgeClass = 'bg-warning text-dark';
                                        if (['completed', 'in production', 'submitted'].includes(status)) badgeClass = 'bg-success';

                                        const row = `
                                            <tr>
                                                <td><span class="fw-bold">${order.id || 'N/A'}</span></td>
                                                <td><span class="badge ${badgeClass}">${order.status || 'N/A'}</span></td>
                                                <td>${order.dealer_reference || 'N/A'}</td>
                                                <td>$${total}</td>
                                                <td>${order.order_date || 'N/A'}</td>
                                                <td>
                                                    <a href="/orders/${order.container_id || order.id}" class="btn btn-sm btn-outline-primary">
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

                                fetchAllBtn.innerHTML = `Fetched ${data.data.length} Orders`;
                                fetchAllBtn.classList.remove('btn-info');
                                fetchAllBtn.classList.add('btn-success');

                                // Log API response
                                // appendApiResponse('GET', '{{ route('my.orders.all') }}', 200, data);
                            }
                        })
                        .catch(err => {
                            alert('An error occurred while fetching all orders.');
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
