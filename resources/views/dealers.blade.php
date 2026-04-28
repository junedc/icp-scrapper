<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dealers - Scraper</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Scraper</a>
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
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Dealers List</h5>
                        <form action="{{ route('dealers.index') }}" method="GET" class="d-flex">
                            <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Search names..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-sm btn-primary">Search</button>
                            @if(request('search'))
                                <a href="{{ route('dealers.index') }}" class="btn btn-sm btn-secondary ms-2">Clear</a>
                            @endif
                        </form>
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
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Company</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($dealers as $dealer)
                                        <tr>
                                            <td>{{ $dealer['id'] ?? 'N/A' }}</td>
                                            <td>
                                                <a href="{{ route('dealers.orders', $dealer['id']) }}">
                                                    {{ $dealer['name'] ?? ($dealer['first_name'] ?? '') . ' ' . ($dealer['last_name'] ?? '') }}
                                                </a>
                                            </td>
                                            <td>{{ $dealer['business_email'] ?? $dealer['email'] ?? 'N/A' }}</td>
                                            <td>{{ $dealer['trading_name'] ?? $dealer['company_name'] ?? ($dealer['company']['name'] ?? 'N/A') }}</td>
                                            <td>
                                                <!-- Action buttons if needed -->
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No dealers found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if(isset($pagination) && $pagination['last_page'] > 1)
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item {{ $pagination['current_page'] <= 1 ? 'disabled' : '' }}">
                                        <a class="page-item page-link" href="{{ route('dealers.index', array_merge(request()->query(), ['page' => $pagination['current_page'] - 1])) }}">Previous</a>
                                    </li>

                                    @for($i = 1; $i <= $pagination['last_page']; $i++)
                                        @if($i == 1 || $i == $pagination['last_page'] || ($i >= $pagination['current_page'] - 2 && $i <= $pagination['current_page'] + 2))
                                            <li class="page-item {{ $pagination['current_page'] == $i ? 'active' : '' }}">
                                                <a class="page-link" href="{{ route('dealers.index', array_merge(request()->query(), ['page' => $i])) }}">{{ $i }}</a>
                                            </li>
                                        @elseif($i == 2 || $i == $pagination['last_page'] - 1)
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        @endif
                                    @endfor

                                    <li class="page-item {{ $pagination['current_page'] >= $pagination['last_page'] ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ route('dealers.index', array_merge(request()->query(), ['page' => $pagination['current_page'] + 1])) }}">Next</a>
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
