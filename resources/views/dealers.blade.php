<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dealers - Scraper</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page">
    <nav class="app-nav no-print">
        <div class="app-nav-inner">
            <a class="app-brand" href="{{ route('dealers.index') }}">Scraper</a>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="button-outline">
                    Logout
                </button>
            </form>
        </div>
    </nav>

    <div class="grid w-full px-4 pb-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(24rem,0.85fr)] lg:px-6">
        <section class="min-h-screen border-b border-slate-800 py-6 lg:border-b-0 lg:border-r lg:pr-6">
            <div class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-heading">Dealer Maintenance</p>
                        <h1 class="mt-2 text-2xl font-semibold text-white">Dealers List</h1>
                        <p class="mt-2 text-sm text-slate-400">Search dealers, inspect users, and jump into impersonation flows.</p>
                    </div>

                    <form action="{{ route('dealers.index') }}" method="GET" class="flex w-full flex-col gap-3 sm:max-w-md sm:flex-row">
                        <input
                            type="text"
                            name="search"
                            class="input-field input-field--sm"
                            placeholder="Search names..."
                            value="{{ request('search') }}"
                        >
                        <button type="submit" class="button-primary whitespace-nowrap">
                            Search
                        </button>
                        @if(request('search'))
                            <a href="{{ route('dealers.index') }}" class="button-secondary whitespace-nowrap">
                                Clear
                            </a>
                        @endif
                    </form>
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
                                            <a href="{{ route('dealers.orders', $dealer['id']) }}" class="data-link">
                                                {{ $dealer['name'] ?? ($dealer['first_name'] ?? '') . ' ' . ($dealer['last_name'] ?? '') }}
                                            </a>
                                        </td>
                                        <td>{{ $dealer['business_email'] ?? $dealer['email'] ?? 'N/A' }}</td>
                                        <td>{{ $dealer['trading_name'] ?? $dealer['company_name'] ?? ($dealer['company']['name'] ?? 'N/A') }}</td>
                                        <td>
                                            <button
                                                type="button"
                                                class="button-accent view-users"
                                                data-dealer-id="{{ $dealer['id'] }}"
                                                data-dealer-name="{{ $dealer['name'] ?? $dealer['trading_name'] ?? 'Dealer' }}"
                                            >
                                                Users
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="empty-state">No dealers found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(isset($pagination) && $pagination['last_page'] > 1)
                        <nav class="mt-6">
                            <div class="pagination-list">
                                <a
                                    href="{{ route('dealers.index', array_merge(request()->query(), ['page' => $pagination['current_page'] - 1])) }}"
                                    class="pagination-link {{ $pagination['current_page'] <= 1 ? 'pagination-link--disabled' : '' }}"
                                >
                                    Previous
                                </a>

                                @for($i = 1; $i <= $pagination['last_page']; $i++)
                                    @if($i == 1 || $i == $pagination['last_page'] || ($i >= $pagination['current_page'] - 2 && $i <= $pagination['current_page'] + 2))
                                        <a
                                            href="{{ route('dealers.index', array_merge(request()->query(), ['page' => $i])) }}"
                                            class="pagination-link {{ $pagination['current_page'] == $i ? 'pagination-link--active' : '' }}"
                                        >
                                            {{ $i }}
                                        </a>
                                    @elseif($i == 2 || $i == $pagination['last_page'] - 1)
                                        <span class="pagination-link pagination-link--disabled">...</span>
                                    @endif
                                @endfor

                                <a
                                    href="{{ route('dealers.index', array_merge(request()->query(), ['page' => $pagination['current_page'] + 1])) }}"
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
                    <p class="mt-2 text-sm text-slate-400">Admin dealer maintenance requests appear here as raw payloads.</p>
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

    <div id="usersModal" class="modal-overlay hidden no-print" role="dialog" aria-modal="true" aria-labelledby="usersModalTitle">
        <div class="modal-panel">
            <div class="modal-header">
                <div>
                    <p class="section-heading">Dealer Users</p>
                    <h2 class="mt-2 text-xl font-semibold text-white" id="usersModalTitle">
                        Users for <span id="modalDealerName"></span>
                    </h2>
                </div>
                <button type="button" class="icon-button" data-close-users-modal aria-label="Close users modal">
                    <span aria-hidden="true" class="text-xl">&times;</span>
                </button>
            </div>

            <div class="modal-body space-y-4">
                <div id="usersLoading" class="flex justify-center py-8">
                    <span class="spinner-lg" aria-hidden="true"></span>
                </div>

                <div id="usersError" class="alert-error hidden"></div>

                <div class="table-shell hidden" id="usersTableContainer">
                    <table class="data-table">
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

                <div id="noUsersMessage" class="empty-state hidden">
                    No non-admin users found for this dealer.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="button-secondary" data-close-users-modal>
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        function appendApiResponse(method, url, status, body) {
            const container = document.getElementById('apiResponseLogsContainer');
            const logDiv = document.getElementById('apiResponseLogs');

            if (!logDiv) {
                return;
            }

            if (container) {
                container.classList.remove('hidden');
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

        document.addEventListener('DOMContentLoaded', function() {
            const usersModal = document.getElementById('usersModal');
            const modalDealerName = document.getElementById('modalDealerName');
            const usersLoading = document.getElementById('usersLoading');
            const usersError = document.getElementById('usersError');
            const usersTableContainer = document.getElementById('usersTableContainer');
            const usersTableBody = document.getElementById('usersTableBody');
            const noUsersMessage = document.getElementById('noUsersMessage');

            function openUsersModal() {
                usersModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeUsersModal() {
                usersModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            document.querySelectorAll('[data-close-users-modal]').forEach(button => {
                button.addEventListener('click', closeUsersModal);
            });

            usersModal.addEventListener('click', function(event) {
                if (event.target === usersModal) {
                    closeUsersModal();
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && !usersModal.classList.contains('hidden')) {
                    closeUsersModal();
                }
            });

            document.querySelectorAll('.view-users').forEach(button => {
                button.addEventListener('click', function() {
                    const dealerId = this.getAttribute('data-dealer-id');
                    const dealerName = this.getAttribute('data-dealer-name');

                    modalDealerName.textContent = dealerName;
                    usersLoading.classList.remove('hidden');
                    usersError.classList.add('hidden');
                    usersTableContainer.classList.add('hidden');
                    noUsersMessage.classList.add('hidden');
                    usersTableBody.innerHTML = '';

                    openUsersModal();

                    fetch(`/dealers/${dealerId}/users`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.api_logs) {
                                data.api_logs.forEach(log => {
                                    appendApiResponse(log.method, log.url, log.status, log.body);
                                });
                            }

                            usersLoading.classList.add('hidden');

                            if (data.error) {
                                usersError.textContent = data.error;
                                usersError.classList.remove('hidden');
                            } else if (data.data.length === 0) {
                                noUsersMessage.classList.remove('hidden');
                            } else {
                                data.data.forEach(user => {
                                    const statusClass = user.status === 'Active'
                                        ? 'status-badge status-badge--success'
                                        : 'status-badge status-badge--neutral';

                                    const row = `
                                        <tr>
                                            <td>${user.id || 'N/A'}</td>
                                            <td>${user.name || 'N/A'}</td>
                                            <td>${user.email || 'N/A'}</td>
                                            <td><span class="${statusClass}">${user.status || 'N/A'}</span></td>
                                            <td>
                                                <button
                                                    type="button"
                                                    class="button-outline impersonate-user"
                                                    data-email="${user.email || ''}"
                                                    data-user-name="${user.name || ''}"
                                                    data-dealer-id="${dealerId || ''}"
                                                    data-dealer-name="${dealerName || ''}"
                                                >
                                                    Impersonate
                                                </button>
                                            </td>
                                        </tr>
                                    `;

                                    usersTableBody.insertAdjacentHTML('beforeend', row);
                                });

                                usersTableContainer.classList.remove('hidden');
                                attachImpersonateEvents();
                            }
                        })
                        .catch(err => {
                            usersLoading.classList.add('hidden');
                            usersError.textContent = 'An error occurred while fetching users.';
                            usersError.classList.remove('hidden');
                            console.error(err);
                        });
                });
            });

            function attachImpersonateEvents() {
                document.querySelectorAll('.impersonate-user').forEach(button => {
                    button.onclick = function() {
                        const email = this.getAttribute('data-email');
                        const dealerId = this.getAttribute('data-dealer-id');
                        const dealerName = this.getAttribute('data-dealer-name');
                        const userName = this.getAttribute('data-user-name');
                        const originalText = this.innerHTML;
                        const btn = this;

                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner" aria-hidden="true"></span>';

                        fetch('/impersonate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                email: email,
                                dealer_id: dealerId,
                                dealer_name: dealerName,
                                user_name: userName
                            })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.api_logs) {
                                    data.api_logs.forEach(log => {
                                        appendApiResponse(log.method, log.url, log.status, log.body);
                                    });
                                }

                                if (data.success) {
                                    window.location.href = '{{ route('my.orders') }}';
                                } else {
                                    alert(`Impersonation failed: ${data.error || 'Unknown error'}`);
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
