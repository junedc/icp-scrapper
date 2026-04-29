<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - {{ $order['order_id'] ?? $id }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page">
    <nav class="app-nav no-print">
        <div class="app-nav-inner">
            <a class="app-brand" href="{{ route('dealers.index') }}">Analytics</a>

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
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3 no-print">
                <a href="javascript:history.back()" class="button-secondary">
                    &larr; Back
                </a>

                <button onclick="window.print()" class="button-primary">
                    Print Order
                </button>
            </div>

            @php
                $statusBadgeClass = 'status-badge status-badge--neutral';
                $normalizedStatus = strtolower($order['status'] ?? '');

                if (in_array($normalizedStatus, ['completed', 'in production', 'submitted'], true)) {
                    $statusBadgeClass = 'status-badge status-badge--success';
                } elseif ($normalizedStatus === 'draft') {
                    $statusBadgeClass = 'status-badge status-badge--warning';
                } elseif ($normalizedStatus !== '') {
                    $statusBadgeClass = 'status-badge status-badge--info';
                }
            @endphp

            <div class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-heading">Production Review</p>
                        <h1 class="mt-2 text-3xl font-semibold text-white">Order #{{ $order['order_id'] ?? $id }}</h1>
                    </div>

                    <span class="{{ $statusBadgeClass }}">
                        {{ strtoupper($order['status'] ?? 'N/A') }}
                    </span>
                </div>

                <div class="surface-panel__body space-y-8">
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div>
                            <p class="section-heading">Order Information</p>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div class="description-item">
                                    <div class="description-label">Date</div>
                                    <div class="description-value">{{ $order['order_date'] ?? 'N/A' }}</div>
                                </div>
                                <div class="description-item">
                                    <div class="description-label">Reference</div>
                                    <div class="description-value">{{ $order['reference'] ?? 'N/A' }}</div>
                                </div>
                                <div class="description-item">
                                    <div class="description-label">State</div>
                                    <div class="description-value">{{ $order['state'] ?? 'N/A' }}</div>
                                </div>
                                <div class="description-item">
                                    <div class="description-label">Generation</div>
                                    <div class="description-value">{{ $order['file_generation_status'] ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="section-heading">Contact Details</p>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                @if(isset($order['contact']))
                                    <div class="description-item">
                                        <div class="description-label">Name</div>
                                        <div class="description-value">{{ $order['contact']['name'] ?? 'N/A' }}</div>
                                    </div>
                                    <div class="description-item">
                                        <div class="description-label">Phone</div>
                                        <div class="description-value">{{ $order['contact']['phone'] ?? 'N/A' }}</div>
                                    </div>
                                    <div class="description-item sm:col-span-2">
                                        <div class="description-label">Email</div>
                                        <div class="description-value">{{ $order['contact']['email'] ?? 'N/A' }}</div>
                                    </div>
                                @else
                                    <div class="alert-info sm:col-span-2">No contact info available.</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="section-heading">Container Info</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            @if(isset($order['container']))
                                <div class="description-item">
                                    <div class="description-label">Container ID</div>
                                    <div class="description-value">{{ $order['container']['id'] ?? 'N/A' }}</div>
                                </div>
                                <div class="description-item">
                                    <div class="description-label">Has PC</div>
                                    <div class="description-value">{{ ($order['container']['has_pc'] ?? 0) ? 'Yes' : 'No' }}</div>
                                </div>
                            @else
                                <div class="alert-info sm:col-span-2">No container info available.</div>
                            @endif
                        </div>
                    </div>

                    @if(isset($order['finance']))
                        <div class="no-print">
                            <p class="section-heading">Financial Summary</p>
                            <div class="finance-card mt-4">
                                <div class="tabs-strip">
                                    <button type="button" class="tab-button tab-button--inactive" data-finance-tab="customer">Customer</button>
                                    <button type="button" class="tab-button tab-button--inactive" data-finance-tab="dealer">Dealer</button>
                                    <button type="button" class="tab-button tab-button--active" data-finance-tab="admin">Admin</button>
                                </div>

                                <div id="customer-tab" data-finance-content="customer" class="mt-6 hidden">
                                    @php $cust = $order['finance']['customer'] ?? []; @endphp
                                    <div class="finance-section-title">Selling</div>
                                    <div class="finance-row"><span>Products</span><span>$ {{ number_format($cust['Products Cost']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>Discount</span><span>$ {{ number_format($cust['Discount']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>Subtotal</span><span>$ {{ number_format($cust['Subtotal']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>GST</span><span>$ {{ number_format($cust['GST']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row finance-total"><span>Order Total</span><span>$ {{ number_format($cust['Order Total']['amount'] ?? 0, 2) }}</span></div>
                                </div>

                                <div id="dealer-tab" data-finance-content="dealer" class="mt-6 hidden">
                                    @php $dealerFin = $order['finance']['dealer'] ?? []; @endphp
                                    <div class="finance-section-title">Buying</div>
                                    <div class="finance-row"><span>Products</span><span>$ {{ number_format($dealerFin['BUY: Products']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>Discount</span><span>$ {{ number_format($dealerFin['BUY: Discount']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>Shipping &amp; Handling</span><span>$ {{ number_format($dealerFin['BUY: Shipping & Handling']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>Subtotal</span><span>$ {{ number_format($dealerFin['BUY: Subtotal']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>GST</span><span>$ {{ number_format($dealerFin['BUY: GST']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row finance-total"><span>Total Cost</span><span>$ {{ number_format($dealerFin['Total Cost']['amount'] ?? 0, 2) }}</span></div>
                                </div>

                                <div id="admin-tab" data-finance-content="admin" class="mt-6">
                                    @php $admin = $order['finance']['admin'] ?? []; @endphp
                                    <div class="finance-section-title">Costs</div>
                                    <div class="finance-row"><span>Accessory Cost</span><span>$ {{ number_format($admin['Accessory Cost']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>Cutting Cost</span><span>$ {{ number_format($admin['Cutting Cost']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>Assembly Cost</span><span>$ {{ number_format($admin['Assembly Cost']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>Total Costs</span><span>$ {{ number_format($admin['Total Costs']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row finance-row-muted"><span>*Includes Wastage</span><span></span></div>
                                    <div class="finance-row"><span>*GST - Indicative</span><span>$ {{ number_format($admin['*GST - Indicative']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row finance-total"><span>*Total Costs - Indicative</span><span>$ {{ number_format($admin['*Total Costs - Indicative']['amount'] ?? 0, 2) }}</span></div>

                                    <div class="finance-section-title">Selling</div>
                                    <div class="finance-row"><span>Products</span><span>$ {{ number_format($admin['SELL: Products']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>Discount</span><span>$ {{ number_format($admin['SELL: Discount']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>Shipping &amp; Handling</span><span>$ {{ number_format($admin['SELL: Shipping & Handling']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>Subtotal</span><span>$ {{ number_format($admin['SELL: Subtotal']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>GST</span><span>$ {{ number_format($admin['SELL: GST']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row finance-total"><span>Order Total</span><span>$ {{ number_format($admin['SELL: Order Total']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>Gross Profit ($)</span><span>$ {{ number_format($admin['Gross Profit ($)']['amount'] ?? 0, 2) }}</span></div>
                                    <div class="finance-row"><span>Gross Profit (%)</span><span>{{ number_format($admin['Gross Profit (%)']['amount'] ?? 0, 1) }}%</span></div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div>
                        <p class="section-heading">Order Items</p>

                        @if(isset($order['mainItems']) && count($order['mainItems']) > 0)
                            <div class="mt-4 space-y-5">
                                @foreach($order['mainItems'] as $index => $item)
                                    <div class="item-card">
                                        <div class="item-card-header">
                                            Item #{{ $index + 1 }}: {{ $item['name'] ?? 'General Item' }} (Qty: {{ $item['count'] ?? 1 }})
                                        </div>

                                        <div class="item-card-body space-y-4">
                                            <div class="item-section">
                                                <div class="item-section-title">Specifications</div>
                                                <div class="key-value-list">
                                                    @foreach($item['required'] ?? [] as $key => $value)
                                                        <div class="key-value-row">
                                                            <div class="key-value-key">{{ $key }}</div>
                                                            <div class="key-value-value">{{ $value }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="item-section">
                                                <div class="item-section-title">General QC</div>
                                                @if(isset($item['qc']['general']))
                                                    <div class="key-value-list">
                                                        @foreach($item['qc']['general'] as $key => $value)
                                                            <div class="key-value-row">
                                                                <div class="key-value-key">{{ $key }}</div>
                                                                <div class="key-value-value">{{ $value }}</div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <p class="text-sm text-slate-400">N/A</p>
                                                @endif
                                            </div>

                                            <div class="item-section">
                                                <div class="item-section-title">Fabrication Checks</div>
                                                @if(isset($item['qc']['fabrication']) && count($item['qc']['fabrication']) > 0)
                                                    <div class="check-list">
                                                        @foreach($item['qc']['fabrication'] as $check)
                                                            <div class="check-row">
                                                                <span>{{ $check['setting'] ?? 'N/A' }}</span>
                                                                <span class="text-slate-500">[ ]</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <p class="text-sm text-slate-400">No specific checks.</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert-info mt-4">No items found for this order.</div>
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
                    <p class="mt-2 text-sm text-slate-400">Production review responses stay visible beside the order details.</p>
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
        document.addEventListener('DOMContentLoaded', function() {
            const financeButtons = document.querySelectorAll('[data-finance-tab]');
            const financePanels = document.querySelectorAll('[data-finance-content]');

            if (!financeButtons.length || !financePanels.length) {
                return;
            }

            function showFinanceTab(target) {
                financeButtons.forEach(button => {
                    const isActive = button.dataset.financeTab === target;
                    button.classList.toggle('tab-button--active', isActive);
                    button.classList.toggle('tab-button--inactive', !isActive);
                });

                financePanels.forEach(panel => {
                    panel.classList.toggle('hidden', panel.dataset.financeContent !== target);
                });
            }

            financeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    showFinanceTab(button.dataset.financeTab);
                });
            });

            showFinanceTab('admin');
        });
    </script>
</body>
</html>
