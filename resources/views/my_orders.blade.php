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
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 text-primary">My Orders</h5>
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
</body>
</html>
