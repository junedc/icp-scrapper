<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Specification - {{ data_get($specification, 'id', $id) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page">
    <nav class="app-nav no-print">
        <div class="app-nav-inner">
            <a class="app-brand" href="{{ route('my.orders') }}">Ordering Portal</a>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="button-outline">
                    Logout
                </button>
            </form>
        </div>
    </nav>

    @php
        $status = data_get($specification, 'status') ?? data_get($specification, 'order.status');
        $normalizedStatus = strtolower((string) $status);
        $statusBadgeClass = 'status-badge status-badge--neutral';

        if (in_array($normalizedStatus, ['completed', 'in production', 'submitted'], true)) {
            $statusBadgeClass = 'status-badge status-badge--success';
        } elseif ($normalizedStatus === 'draft') {
            $statusBadgeClass = 'status-badge status-badge--warning';
        } elseif ($normalizedStatus !== '') {
            $statusBadgeClass = 'status-badge status-badge--info';
        }

        $orderId = data_get($specification, 'order_id')
            ?? data_get($specification, 'id')
            ?? $id;
        $orderDate = data_get($specification, 'order_date')
            ?? data_get($specification, 'date')
            ?? data_get($specification, 'order.order_date');
        $jobAddress = collect([
            data_get($specification, 'job_address'),
            data_get($specification, 'job_address_line'),
            data_get($specification, 'contact.address'),
            data_get($specification, 'customer.address'),
            data_get($specification, 'address'),
        ])->filter(fn ($value) => filled($value))->first();

        $customerName = data_get($specification, 'customer_name')
            ?? data_get($specification, 'contact.name')
            ?? data_get($specification, 'customer.name');
        $customerEmail = data_get($specification, 'contact.email')
            ?? data_get($specification, 'customer.email')
            ?? data_get($specification, 'email');
        $customerPhone = data_get($specification, 'contact.phone')
            ?? data_get($specification, 'customer.phone')
            ?? data_get($specification, 'phone');
        $customerAddress = data_get($specification, 'contact.address')
            ?? data_get($specification, 'customer.address')
            ?? $jobAddress;

        $dealerName = data_get($specification, 'dealer.name')
            ?? data_get($specification, 'dealer_name')
            ?? data_get($specification, 'dealer_reference')
            ?? session('ordering_portal_context.dealer_name');
        $dealerEmail = data_get($specification, 'dealer.email')
            ?? data_get($specification, 'dealer_email')
            ?? session('ordering_portal_context.user_email');
        $dealerPhone = data_get($specification, 'dealer.phone')
            ?? data_get($specification, 'dealer_phone');

        $customerFinance = data_get($specification, 'finance.customer', []);
        $dealerFinance = data_get($specification, 'finance.dealer', []);
        $lineItems = data_get($specification, 'items')
            ?? data_get($specification, 'mainItems')
            ?? data_get($specification, 'lines')
            ?? [];

        $summaryTabs = [
            'customer' => [
                'label' => 'Customer',
                'sections' => [
                    'Selling' => [
                        'Products' => data_get($customerFinance, 'Products Cost.amount')
                            ?? data_get($customerFinance, 'Products.amount')
                            ?? data_get($customerFinance, 'SELL: Products.amount'),
                        'Discount' => data_get($customerFinance, 'Discount.amount')
                            ?? data_get($customerFinance, 'SELL: Discount.amount'),
                        'Subtotal' => data_get($customerFinance, 'Subtotal.amount')
                            ?? data_get($customerFinance, 'SELL: Subtotal.amount'),
                        'GST' => data_get($customerFinance, 'GST.amount')
                            ?? data_get($customerFinance, 'SELL: GST.amount'),
                        'Order Total' => data_get($customerFinance, 'Order Total.amount')
                            ?? data_get($customerFinance, 'SELL: Order Total.amount'),
                    ],
                ],
            ],
            'dealer' => [
                'label' => 'Dealer',
                'sections' => [
                    'Buying' => [
                        'Products' => data_get($dealerFinance, 'BUY: Products.amount')
                            ?? data_get($dealerFinance, 'Products.amount'),
                        'Discount' => data_get($dealerFinance, 'BUY: Discount.amount'),
                        'Shipping & Handling' => data_get($dealerFinance, 'BUY: Shipping & Handling.amount'),
                        'Subtotal' => data_get($dealerFinance, 'BUY: Subtotal.amount'),
                        'GST' => data_get($dealerFinance, 'BUY: GST.amount'),
                        'Total Cost' => data_get($dealerFinance, 'Total Cost.amount'),
                    ],
                    'Selling' => [
                        'Products Markup %' => data_get($dealerFinance, 'Products Markup (%).amount')
                            ?? data_get($dealerFinance, 'Products Markup %.amount')
                            ?? data_get($dealerFinance, 'Gross Profit (%).amount'),
                        'Products' => data_get($dealerFinance, 'SELL: Products.amount'),
                        'Discount' => data_get($dealerFinance, 'SELL: Discount.amount'),
                        'Shipping & Handling' => data_get($dealerFinance, 'SELL: Shipping & Handling.amount'),
                        'Subtotal' => data_get($dealerFinance, 'SELL: Subtotal.amount'),
                        'GST' => data_get($dealerFinance, 'SELL: GST.amount'),
                        'Order Total' => data_get($dealerFinance, 'SELL: Order Total.amount'),
                        'Gross Profit ($)' => data_get($dealerFinance, 'Gross Profit ($).amount'),
                        'Gross Profit (%)' => data_get($dealerFinance, 'Gross Profit (%).amount'),
                    ],
                ],
            ],
        ];
    @endphp

    <div class="grid w-full px-4 pb-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(24rem,0.85fr)] lg:px-6">
        <section class="min-h-screen border-b border-slate-800 py-6 lg:border-b-0 lg:border-r lg:pr-6">
            <div class="surface-panel">
                <div class="surface-panel__body space-y-8">
                    <div class="spec-hero">
                        <div class="spec-info-grid">
                            <section class="spec-info-block">
                                <p class="section-heading">Order Details</p>
                                <div class="spec-info-list">
                                    <div><span class="spec-key">Order ID Number:</span> {{ $orderId ?? 'N/A' }}</div>
                                    <div><span class="spec-key">Date:</span> {{ $orderDate ?? 'N/A' }}</div>
                                    <div><span class="spec-key">Job Address:</span> {{ $jobAddress ?? 'N/A' }}</div>
                                </div>
                            </section>

                            <section class="spec-info-block">
                                <p class="section-heading">Customer</p>
                                <div class="spec-info-list">
                                    <div><span class="spec-key">Name:</span> {{ $customerName ?? 'N/A' }}</div>
                                    <div><span class="spec-key">Email:</span> {{ $customerEmail ?? 'N/A' }}</div>
                                    <div><span class="spec-key">Phone:</span> {{ $customerPhone ?? 'N/A' }}</div>
                                    <div><span class="spec-key">Address:</span> {{ $customerAddress ?? 'N/A' }}</div>
                                </div>
                            </section>

                            <section class="spec-info-block">
                                <p class="section-heading">Dealer</p>
                                <div class="flex items-center gap-3">
                                    <span class="{{ $statusBadgeClass }}">{{ strtoupper($status ?? 'N/A') }}</span>
                                </div>
                                <div class="spec-info-list mt-4">
                                    <div><span class="spec-key">Dealer:</span> {{ $dealerName ?? 'N/A' }}</div>
                                    <div><span class="spec-key">Email:</span> {{ $dealerEmail ?? 'N/A' }}</div>
                                    <div><span class="spec-key">Phone:</span> {{ $dealerPhone ?? 'N/A' }}</div>
                                </div>
                            </section>
                        </div>

                        <aside class="finance-card spec-summary-card">
                            <p class="section-heading">Order Summary</p>

                            <div class="tabs-strip mt-4">
                                @foreach($summaryTabs as $tabKey => $tab)
                                    <button
                                        type="button"
                                        class="tab-button {{ $tabKey === 'dealer' ? 'tab-button--active' : 'tab-button--inactive' }}"
                                        data-spec-summary-tab="{{ $tabKey }}"
                                    >
                                        {{ $tab['label'] }}
                                    </button>
                                @endforeach
                            </div>

                            @foreach($summaryTabs as $tabKey => $tab)
                                <div
                                    class="mt-6 {{ $tabKey === 'dealer' ? '' : 'hidden' }}"
                                    data-spec-summary-panel="{{ $tabKey }}"
                                >
                                    @foreach($tab['sections'] as $sectionTitle => $rows)
                                        @php
                                            $visibleRows = collect($rows)
                                                ->filter(fn ($value) => $value !== null && $value !== '')
                                                ->all();
                                        @endphp

                                        @if(count($visibleRows) > 0)
                                            <div class="finance-section-title">{{ $sectionTitle }}</div>

                                            @foreach($visibleRows as $label => $value)
                                                <div class="finance-row {{ in_array($label, ['Order Total', 'Total Cost'], true) ? 'finance-total' : '' }}">
                                                    <span>{{ $label }}</span>
                                                    <span>
                                                        @if(str_contains($label, '%'))
                                                            {{ number_format((float) $value, 1) }}%
                                                        @else
                                                            $ {{ number_format((float) $value, 2) }}
                                                        @endif
                                                    </span>
                                                </div>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </div>
                            @endforeach
                        </aside>
                    </div>

                    <div>
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <p class="section-heading">Product Details</p>
                                <p class="mt-2 text-sm text-slate-400">Dealer-facing product specifications and pricing from the ordering portal payload.</p>
                            </div>
                            <span class="status-badge status-badge--neutral">
                                {{ is_array($lineItems) ? count($lineItems) : 0 }} items
                            </span>
                        </div>

                        @if(is_array($lineItems) && count($lineItems) > 0)
                            <div class="space-y-5">
                                @foreach($lineItems as $index => $item)
                                    @php
                                        $itemTitle = data_get($item, 'name')
                                            ?? data_get($item, 'title')
                                            ?? data_get($item, 'required.Name')
                                            ?? 'Untitled item';
                                        $itemCost = data_get($item, 'cost')
                                            ?? data_get($item, 'pricing.cost')
                                            ?? data_get($item, 'finance.cost');
                                        $itemPrice = data_get($item, 'price')
                                            ?? data_get($item, 'pricing.price')
                                            ?? data_get($item, 'finance.price');
                                        $leftFields = [
                                            'Location' => data_get($item, 'location') ?? data_get($item, 'required.Location'),
                                            'Width' => data_get($item, 'width') ?? data_get($item, 'required.Width'),
                                            'Drop' => data_get($item, 'drop') ?? data_get($item, 'required.Drop'),
                                            'Type' => data_get($item, 'type') ?? data_get($item, 'required.Type'),
                                        ];
                                        $rightFields = [
                                            'Style' => data_get($item, 'style') ?? data_get($item, 'required.Style'),
                                            'Grade' => data_get($item, 'grade') ?? data_get($item, 'required.Grade'),
                                            'Frame Colour' => data_get($item, 'frame_colour') ?? data_get($item, 'required.Frame Colour'),
                                            'Finish' => data_get($item, 'finish') ?? data_get($item, 'required.Finish'),
                                        ];
                                    @endphp

                                    <div class="item-card">
                                        <div class="item-card-header">
                                            Item #{{ $index + 1 }}: {{ $itemTitle }}
                                        </div>

                                        <div class="item-card-body">
                                            <div class="spec-product-grid">
                                                <div class="spec-product-visual">
                                                    <div class="spec-product-icon">
                                                        <span></span>
                                                        <span></span>
                                                    </div>
                                                </div>

                                                <div class="spec-product-fields">
                                                    @foreach($leftFields as $label => $value)
                                                        @if(filled($value))
                                                            <div class="spec-field-row">
                                                                <span class="spec-field-label">{{ $label }}:</span>
                                                                <span>{{ $value }}</span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>

                                                <div class="spec-product-fields">
                                                    @foreach($rightFields as $label => $value)
                                                        @if(filled($value))
                                                            <div class="spec-field-row">
                                                                <span class="spec-field-label">{{ $label }}:</span>
                                                                <span>{{ $value }}</span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>

                                                <div class="spec-product-pricing">
                                                    @if(is_numeric($itemCost))
                                                        <div><span class="spec-field-label">Cost</span> ${{ number_format((float) $itemCost, 2) }}</div>
                                                    @endif

                                                    @if(is_numeric($itemPrice))
                                                        <div><span class="spec-field-label">Price</span> ${{ number_format((float) $itemPrice, 2) }}</div>
                                                    @endif
                                                </div>
                                            </div>

                                            @if(isset($item['required']) && is_array($item['required']) && count($item['required']) > 0)
                                                <div class="item-section mt-5">
                                                    <div class="item-section-title">Additional Specifications</div>
                                                    <div class="key-value-list">
                                                        @foreach($item['required'] as $key => $value)
                                                            @if(filled($value) && ! in_array($key, ['Location', 'Width', 'Drop', 'Type', 'Style', 'Grade', 'Frame Colour', 'Finish', 'Name'], true))
                                                                <div class="key-value-row">
                                                                    <div class="key-value-key">{{ $key }}</div>
                                                                    <div class="key-value-value">{{ $value }}</div>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert-info mt-4">No line items were returned for this specification.</div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <aside class="min-h-screen bg-slate-900/60 no-print" id="apiResponseLogsContainer">
            <div class="log-panel lg:sticky lg:top-0">
                <div class="mb-6">
                    <p class="section-heading">Specification JSON</p>
                    <h2 class="mt-3 text-2xl font-semibold text-white">Order Payload</h2>
                    <p class="mt-2 text-sm text-slate-400">The raw ordering portal specification response stays visible here with both raw and tree tabs.</p>
                </div>

                <div id="apiResponseLogs" class="space-y-4">
                    <div class="log-entry">
                        <div class="log-meta">GET {{ config('services.starline_api.base_url') }}/api/ordering-portal/my-orders/{{ $id }}/specification</div>
                        <textarea class="log-textarea" rows="18" readonly>{{ json_encode($specification, JSON_PRETTY_PRINT) }}</textarea>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('[data-spec-summary-tab]');
            const panels = document.querySelectorAll('[data-spec-summary-panel]');

            if (!buttons.length || !panels.length) {
                return;
            }

            function showSummaryTab(target) {
                buttons.forEach((button) => {
                    const isActive = button.dataset.specSummaryTab === target;
                    button.classList.toggle('tab-button--active', isActive);
                    button.classList.toggle('tab-button--inactive', !isActive);
                });

                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.specSummaryPanel !== target);
                });
            }

            buttons.forEach((button) => {
                button.addEventListener('click', function() {
                    showSummaryTab(button.dataset.specSummaryTab);
                });
            });

            showSummaryTab('dealer');
        });
    </script>
</body>
</html>
