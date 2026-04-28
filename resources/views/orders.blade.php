<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Scraper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dealers.index') }}">Scraper</a>
            <div class="navbar-nav ms-auto">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-link nav-link">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="mb-3">
                    <a href="{{ route('dealers.index') }}" class="btn btn-secondary">&larr; Back to Dealers</a>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Orders for Dealer #{{ $id }}</h5>
                    </div>
                    <div class="card-body">
                        @if(isset($error))
                            <div class="alert alert-danger">{{ $error }}</div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
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
                                            <tr>
                                                <td>
                                                    {{ $order['id'] ?? $order['id_with_prefix'] ?? 'N/A' }}
                                                </td>
                                                <td>
                                                    <a href="{{ route('orders.show', $order['container_id'] ?? $order['container']['id'] ?? 0) }}">
                                                        {{ $order['container_id'] ?? $order['container']['id'] ?? 'N/A' }}
                                                    </a>
                                                </td>
                                                <td>{{ $order['order_number'] ?? $order['number'] ?? $order['dealer_reference'] ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ in_array(strtolower($order['status'] ?? ''), ['completed', 'in production']) ? 'success' : 'info' }}">
                                                        {{ $order['status'] ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td>{{ $order['total'] ?? 'N/A' }}</td>
                                                <td>{{ $order['order_date'] ?? $order['created_date'] ?? (isset($order['created_at']) ? date('Y-m-d H:i', strtotime($order['created_at'])) : 'N/A') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">No orders found for this dealer.</td>
                                            </tr>
                                        @endforelse
                                    @else
                                        <tr>
                                            <td colspan="5" class="text-center">Unable to load orders.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        @if(isset($pagination) && $pagination['last_page'] > 1)
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item {{ $pagination['current_page'] <= 1 ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ route('dealers.orders', array_merge(['id' => $id], request()->query(), ['page' => $pagination['current_page'] - 1])) }}">Previous</a>
                                    </li>

                                    @for($i = 1; $i <= $pagination['last_page']; $i++)
                                        @if($i == 1 || $i == $pagination['last_page'] || ($i >= $pagination['current_page'] - 2 && $i <= $pagination['current_page'] + 2))
                                            <li class="page-item {{ $pagination['current_page'] == $i ? 'active' : '' }}">
                                                <a class="page-link" href="{{ route('dealers.orders', array_merge(['id' => $id], request()->query(), ['page' => $i])) }}">{{ $i }}</a>
                                            </li>
                                        @elseif($i == 2 || $i == $pagination['last_page'] - 1)
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        @endif
                                    @endfor

                                    <li class="page-item {{ $pagination['current_page'] >= $pagination['last_page'] ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ route('dealers.orders', array_merge(['id' => $id], request()->query(), ['page' => $pagination['current_page'] + 1])) }}">Next</a>
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
