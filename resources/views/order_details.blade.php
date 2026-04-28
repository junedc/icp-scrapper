<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - {{ $order['order_id'] ?? $id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; }
            .card-header { background-color: #f8f9fa !important; color: black !important; }
            body { background-color: white !important; }
        }
        .section-title {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 5px;
            margin-bottom: 15px;
            color: #0d6efd;
        }
        .info-label { font-weight: bold; color: #6c757d; }

        .finance-card {
            background-color: #f0f4f8;
            border-radius: 12px;
            padding: 20px;
            max-width: 400px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .finance-tabs {
            background-color: #b8c2cc;
            border-radius: 8px;
            display: flex;
            padding: 4px;
            margin-bottom: 20px;
        }
        .finance-tab {
            flex: 1;
            text-align: center;
            padding: 6px;
            border-radius: 6px;
            cursor: pointer;
            color: #333;
            font-weight: 500;
        }
        .finance-tab.active {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .finance-tab.admin-active {
            border: 2px solid #48bb78;
        }
        .finance-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 1.1rem;
        }
        .finance-section-header {
            font-weight: bold;
            font-size: 1.2rem;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .finance-total {
            font-weight: bold;
        }
        .finance-amount {
            text-align: right;
            min-width: 100px;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 no-print">
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
        <div class="row no-print mb-4">
            <div class="col-12 d-flex justify-content-between">
                <a href="javascript:history.back()" class="btn btn-secondary">&larr; Back</a>
                <button onclick="window.print()" class="btn btn-primary">Print Order</button>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Order #{{ $order['order_id'] ?? $id }}</h5>
                <span class="badge bg-light text-primary">{{ strtoupper($order['status'] ?? 'N/A') }}</span>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <h6 class="section-title">Order Information</h6>
                        <div class="mb-1"><span class="info-label">Date:</span> {{ $order['order_date'] ?? 'N/A' }}</div>
                        <div class="mb-1"><span class="info-label">Reference:</span> {{ $order['reference'] ?? 'N/A' }}</div>
                        <div class="mb-1"><span class="info-label">State:</span> {{ $order['state'] ?? 'N/A' }}</div>
                        <div class="mb-1"><span class="info-label">Generation:</span> {{ $order['file_generation_status'] ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-4">
                        <h6 class="section-title">Contact Details</h6>
                        @if(isset($order['contact']))
                            <div class="mb-1"><span class="info-label">Name:</span> {{ $order['contact']['name'] ?? 'N/A' }}</div>
                            <div class="mb-1"><span class="info-label">Phone:</span> {{ $order['contact']['phone'] ?? 'N/A' }}</div>
                            <div class="mb-1"><span class="info-label">Email:</span> {{ $order['contact']['email'] ?? 'N/A' }}</div>
                        @else
                            <p class="text-muted">No contact info available</p>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <h6 class="section-title">Container Info</h6>
                        @if(isset($order['container']))
                            <div class="mb-1"><span class="info-label">ID:</span> {{ $order['container']['id'] ?? 'N/A' }}</div>
                            <div class="mb-1"><span class="info-label">Has PC:</span> {{ ($order['container']['has_pc'] ?? 0) ? 'Yes' : 'No' }}</div>
                        @else
                            <p class="text-muted">No container info</p>
                        @endif
                    </div>
                </div>

                @if(isset($order['finance']))
                <div class="row mb-4 no-print">
                    <div class="col-12">
                        <h6 class="section-title">Financial Summary</h6>
                        <div class="finance-card">
                            <div class="finance-tabs">
                                <div class="finance-tab" onclick="showTab('customer')">Customer</div>
                                <div class="finance-tab" onclick="showTab('dealer')">Dealer</div>
                                <div class="finance-tab active admin-active" onclick="showTab('admin')">Admin</div>
                            </div>

                            <div id="customer-tab" class="finance-content d-none">
                                @php $cust = $order['finance']['customer'] ?? []; @endphp
                                <div class="finance-section-header">Selling</div>
                                <div class="finance-row"><span>Products</span> <span class="finance-amount">$ {{ number_format($cust['Products Cost']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row"><span>Discount</span> <span class="finance-amount">$ {{ number_format($cust['Discount']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row"><span>Subtotal</span> <span class="finance-amount">$ {{ number_format($cust['Subtotal']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row"><span>Gst</span> <span class="finance-amount">$ {{ number_format($cust['GST']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row finance-total"><span>Order Total</span> <span class="finance-amount">$ {{ number_format($cust['Order Total']['amount'] ?? 0, 2) }}</span></div>
                            </div>

                            <div id="dealer-tab" class="finance-content d-none">
                                @php $dealerFin = $order['finance']['dealer'] ?? []; @endphp
                                <div class="finance-section-header">Buying</div>
                                <div class="finance-row"><span>Products</span> <span class="finance-amount">$ {{ number_format($dealerFin['BUY: Products']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row"><span>Discount</span> <span class="finance-amount">$ {{ number_format($dealerFin['BUY: Discount']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row"><span>Shipping & Handling</span> <span class="finance-amount">$ {{ number_format($dealerFin['BUY: Shipping & Handling']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row"><span>Subtotal</span> <span class="finance-amount">$ {{ number_format($dealerFin['BUY: Subtotal']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row"><span>Gst</span> <span class="finance-amount">$ {{ number_format($dealerFin['BUY: GST']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row finance-total"><span>Total Cost</span> <span class="finance-amount">$ {{ number_format($dealerFin['Total Cost']['amount'] ?? 0, 2) }}</span></div>
                            </div>

                            <div id="admin-tab" class="finance-content">
                                @php $admin = $order['finance']['admin'] ?? []; @endphp
                                <div class="finance-section-header">Costs</div>
                                <div class="finance-row"><span>Accessory Cost</span> <span class="finance-amount">$ {{ number_format($admin['Accessory Cost']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row"><span>Cutting Cost</span> <span class="finance-amount">$ {{ number_format($admin['Cutting Cost']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row"><span>Assembly Cost</span> <span class="finance-amount">$ {{ number_format($admin['Assembly Cost']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row"><span>Total Costs</span> <span class="finance-amount">$ {{ number_format($admin['Total Costs']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row mt-2"><span>*Includes Wastage</span></div>
                                <div class="finance-row"><span>*Gst - Indicative</span> <span class="finance-amount">$ {{ number_format($admin['*GST - Indicative']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row finance-total"><span>*Total Costs - Indicative</span> <span class="finance-amount">$ {{ number_format($admin['*Total Costs - Indicative']['amount'] ?? 0, 2) }}</span></div>

                                <div class="finance-section-header">Selling</div>
                                <div class="finance-row"><span>Products</span> <span class="finance-amount">$ {{ number_format($admin['SELL: Products']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row"><span>Discount</span> <span class="finance-amount">$ {{ number_format($admin['SELL: Discount']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row"><span>Shipping & Handling</span> <span class="finance-amount">$ {{ number_format($admin['SELL: Shipping & Handling']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row"><span>Subtotal</span> <span class="finance-amount">$ {{ number_format($admin['SELL: Subtotal']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row"><span>Gst</span> <span class="finance-amount">$ {{ number_format($admin['SELL: GST']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row finance-total"><span>Order Total</span> <span class="finance-amount">$ {{ number_format($admin['SELL: Order Total']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row mt-2"><span>Gross Profit ($)</span> <span class="finance-amount">$ {{ number_format($admin['Gross Profit ($)']['amount'] ?? 0, 2) }}</span></div>
                                <div class="finance-row"><span>Gross Profit (%)</span> <span class="finance-amount">{{ number_format($admin['Gross Profit (%)']['amount'] ?? 0, 1) }}%</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    function showTab(tabName) {
                        // Hide all content
                        document.querySelectorAll('.finance-content').forEach(el => el.classList.add('d-none'));
                        // Remove active class from all tabs
                        document.querySelectorAll('.finance-tab').forEach(el => {
                            el.classList.remove('active', 'admin-active');
                        });

                        // Show selected content
                        document.getElementById(tabName + '-tab').classList.remove('d-none');

                        // Set active tab styling
                        const activeTab = Array.from(document.querySelectorAll('.finance-tab')).find(el => el.textContent.toLowerCase() === tabName);
                        if (activeTab) {
                            activeTab.classList.add('active');
                            if (tabName === 'admin') {
                                activeTab.classList.add('admin-active');
                            }
                        }
                    }
                </script>
                @endif

                @if(isset($order['mainItems']) && count($order['mainItems']) > 0)
                    <h5 class="section-title mt-4">Order Items</h5>
                    @foreach($order['mainItems'] as $index => $item)
                        <div class="card mb-4 border-secondary shadow-sm">
                            <div class="card-header bg-secondary text-white py-1">
                                <small>Item #{{ $index + 1 }}: {{ $item['name'] ?? 'General Item' }} (Qty: {{ $item['count'] ?? 1 }})</small>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h6 class="text-secondary border-bottom pb-1">Specifications</h6>
                                        <div class="small">
                                            @foreach($item['required'] ?? [] as $key => $value)
                                                <div class="mb-1"><strong>{{ $key }}:</strong> {{ $value }}</div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="text-secondary border-bottom pb-1">General QC</h6>
                                        <div class="small">
                                            @if(isset($item['qc']['general']))
                                                @foreach($item['qc']['general'] as $key => $value)
                                                    <div class="mb-1"><strong>{{ $key }}:</strong> {{ $value }}</div>
                                                @endforeach
                                            @else
                                                <p class="text-muted">N/A</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="text-secondary border-bottom pb-1">Fabrication Checks</h6>
                                        <div class="small">
                                            @if(isset($item['qc']['fabrication']) && count($item['qc']['fabrication']) > 0)
                                                <ul class="list-unstyled mb-0">
                                                    @foreach($item['qc']['fabrication'] as $check)
                                                        <li class="mb-1 d-flex justify-content-between border-bottom border-light">
                                                            <span>{{ $check['setting'] ?? 'N/A' }}</span>
                                                            <span class="text-muted">[ ]</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="text-muted">No specific checks</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="alert alert-info">No items found for this order.</div>
                @endif
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
</body>
</html>
