<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Scraper</title>
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

    <div class="container pb-5">
        <div class="row">
            <div class="col-12">
                <div class="mb-3 d-flex justify-content-between">
                    <a href="javascript:history.back()" class="btn btn-secondary">&larr; Back</a>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Order #{{ $order['order_id'] ?? $id }} Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Status:</strong> <span class="badge bg-info text-dark">{{ $order['status'] ?? 'N/A' }}</span></p>
                                <p><strong>Order Date:</strong> {{ $order['order_date'] ?? 'N/A' }}</p>
                                <p><strong>State:</strong> {{ $order['state'] ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Reference:</strong> {{ $order['reference'] ?? 'N/A' }}</p>
                                <p><strong>File Generation:</strong> {{ $order['file_generation_status'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if(isset($order['mainItems']) && count($order['mainItems']) > 0)
                    <h4 class="mb-3">Order Items</h4>
                    @foreach($order['mainItems'] as $index => $item)
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Item #{{ $index + 1 }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Requirements</h6>
                                        <ul class="list-unstyled">
                                            @foreach($item['required'] ?? [] as $key => $value)
                                                <li><strong>{{ $key }}:</strong> {{ $value }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        @if(isset($item['qc']['general']))
                                            <h6>General QC</h6>
                                            <ul class="list-unstyled">
                                                @foreach($item['qc']['general'] as $key => $value)
                                                    <li><strong>{{ $key }}:</strong> {{ $value }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>

                                @if(isset($item['qc']['fabrication']) && count($item['qc']['fabrication']) > 0)
                                    <div class="mt-3">
                                        <h6>Fabrication Checks</h6>
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Check</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($item['qc']['fabrication'] as $check)
                                                    <tr>
                                                        <td>{{ $check['setting'] ?? 'N/A' }}</td>
                                                        <td><span class="text-muted">Pending</span></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="alert alert-info">No items found for this order.</div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
