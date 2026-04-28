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
                                                <button type="button" class="btn btn-sm btn-info text-white view-users" data-dealer-id="{{ $dealer['id'] }}" data-dealer-name="{{ $dealer['name'] ?? $dealer['trading_name'] ?? 'Dealer' }}">
                                                    Users
                                                </button>
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
    <div class="modal fade" id="usersModal" tabindex="-1" aria-labelledby="usersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="usersModalLabel">Users for <span id="modalDealerName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="usersLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="usersError" class="alert alert-danger d-none"></div>
                    <div class="table-responsive d-none" id="usersTableContainer">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody"></tbody>
                        </table>
                    </div>
                    <div id="noUsersMessage" class="text-center py-4 d-none">
                        No non-admin users found for this dealer.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
            const usersModal = new bootstrap.Modal(document.getElementById('usersModal'));
            const modalDealerName = document.getElementById('modalDealerName');
            const usersLoading = document.getElementById('usersLoading');
            const usersError = document.getElementById('usersError');
            const usersTableContainer = document.getElementById('usersTableContainer');
            const usersTableBody = document.getElementById('usersTableBody');
            const noUsersMessage = document.getElementById('noUsersMessage');

            document.querySelectorAll('.view-users').forEach(button => {
                button.addEventListener('click', function() {
                    const dealerId = this.getAttribute('data-dealer-id');
                    const dealerName = this.getAttribute('data-dealer-name');

                    modalDealerName.textContent = dealerName;
                    usersLoading.classList.remove('d-none');
                    usersError.classList.add('d-none');
                    usersTableContainer.classList.add('d-none');
                    noUsersMessage.classList.add('d-none');
                    usersTableBody.innerHTML = '';

                    usersModal.show();

                    fetch(`/dealers/${dealerId}/users`)
                        .then(response => response.json())
                        .then(data => {
                            // Log external API calls if available
                            if (data.api_logs) {
                                data.api_logs.forEach(log => {
                                    appendApiResponse(log.method, log.url, log.status, log.body);
                                });
                            }

                            usersLoading.classList.add('d-none');
                            if (data.error) {
                                usersError.textContent = data.error;
                                usersError.classList.remove('d-none');
                            } else if (data.data.length === 0) {
                                noUsersMessage.classList.remove('d-none');
                            } else {
                                data.data.forEach(user => {
                                    const row = `
                                        <tr>
                                            <td>${user.id || 'N/A'}</td>
                                            <td>${user.name || 'N/A'}</td>
                                            <td>${user.email || 'N/A'}</td>
                                            <td>
                                                <span class="badge bg-${user.status === 'Active' ? 'success' : 'secondary'}">
                                                    ${user.status || 'N/A'}
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary impersonate-user" data-email="${user.email}">
                                                    Impersonate
                                                </button>
                                            </td>
                                        </tr>
                                    `;
                                    usersTableBody.insertAdjacentHTML('beforeend', row);
                                });
                                usersTableContainer.classList.remove('d-none');

                                // Re-attach impersonate events
                                attachImpersonateEvents();
                            }
                        })
                        .catch(err => {
                            usersLoading.classList.add('d-none');
                            usersError.textContent = 'An error occurred while fetching users.';
                            usersError.classList.remove('d-none');
                            console.error(err);
                        });
                });
            });

            function attachImpersonateEvents() {
                document.querySelectorAll('.impersonate-user').forEach(button => {
                    button.onclick = function() {
                        const email = this.getAttribute('data-email');
                        const originalText = this.innerHTML;
                        const btn = this;

                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

                        fetch('/impersonate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ email: email })
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Log external API calls if available
                            if (data.api_logs) {
                                data.api_logs.forEach(log => {
                                    appendApiResponse(log.method, log.url, log.status, log.body);
                                });
                            }

                            if (data.success) {
                                window.location.href = '{{ route('my.orders') }}';
                            } else {
                                alert('Impersonation failed: ' + (data.error || 'Unknown error'));
                            }
                        })
                        .catch(err => {
                            alert('An error occurred during impersonation.');
                            console.error(err);
                        })
                        .finally(() => {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        });
                    };
                });
            }
        });
    </script>
</body>
</html>
