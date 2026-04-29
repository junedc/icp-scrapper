<?php

namespace Tests\Feature;

use App\Models\DealerLeadSnapshot;
use App\Models\DealerOrderSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DealerControllerOrderingPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_orders_page_returns_requested_status_page(): void
    {
        Http::fake(function (HttpRequest $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            $this->assertSame('Draft', $query['filter']['status'] ?? null);
            $this->assertSame(' ', $query['filter']['search'] ?? null);
            $this->assertSame('4', (string) ($query['page'] ?? ''));

            return Http::response([
                'data' => [
                    ['id' => 404, 'status' => 'Draft'],
                ],
                'pagination' => [
                    'current_page' => 4,
                    'total_pages' => 7,
                    'total' => 64,
                    'per_page' => 10,
                ],
            ]);
        });

        $response = $this
            ->withSession([
                'impersonated_token' => 'impersonated-token',
                'ordering_portal_context' => [
                    'dealer_id' => 77,
                    'dealer_name' => 'Acme Windows',
                    'user_name' => 'Jane Dealer',
                    'user_email' => 'jane@example.com',
                    'source' => 'impersonation',
                ],
            ])
            ->getJson(route('my.orders.page', ['status' => 'Draft', 'page' => 4]));

        $response
            ->assertOk()
            ->assertJsonPath('selected_status', 'Draft')
            ->assertJsonPath('pagination.current_page', 4)
            ->assertJsonPath('pagination.last_page', 7)
            ->assertJsonPath('data.0.id', 404);

        $snapshot = DealerOrderSnapshot::query()->firstOrFail();

        $this->assertSame('acme-windows-77::order-404', $snapshot->record_key);
        $this->assertSame('acme-windows-77', $snapshot->dealer_scope);
        $this->assertSame('Acme Windows', $snapshot->dealer_name);
        $this->assertSame('Jane Dealer', $snapshot->dealer_user_name);
        $this->assertSame('jane@example.com', $snapshot->dealer_user_email);
        $this->assertSame('/api/ordering-portal/my-orders', $snapshot->source_endpoint);
        $this->assertSame('Draft', $snapshot->queried_status);
        $this->assertSame(4, $snapshot->queried_page);
        $this->assertSame(404, $snapshot->external_order_id);
    }

    public function test_my_orders_all_uses_blank_search_and_fetches_every_open_page(): void
    {
        $openPagesRequested = [];

        Http::fake(function (HttpRequest $request) use (&$openPagesRequested) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            $this->assertSame('-by_date', $query['sort'] ?? null);
            $this->assertSame(' ', $query['filter']['search'] ?? null);

            $status = $query['filter']['status'] ?? null;
            $page = (int) ($query['page'] ?? 1);

            if ($status === 'Open') {
                $openPagesRequested[] = $page;

                return Http::response([
                    'data' => [
                        ['id' => 2000 + $page, 'status' => 'Open'],
                    ],
                    'pagination' => [
                        'total_pages' => 12,
                    ],
                ]);
            }

            return Http::response([
                'data' => [],
                'pagination' => [
                    'total_pages' => 1,
                ],
            ]);
        });

        $response = $this
            ->withSession([
                'impersonated_token' => 'impersonated-token',
                'ordering_portal_context' => [
                    'dealer_id' => 91,
                    'dealer_name' => 'North Coast Doors',
                    'user_name' => 'Sam Dealer',
                    'user_email' => 'sam@example.com',
                    'source' => 'impersonation',
                ],
            ])
            ->getJson(route('my.orders.all'));

        $response
            ->assertOk()
            ->assertJsonPath('total', 12)
            ->assertJsonCount(12, 'data')
            ->assertJsonPath('data.0.id', 2001)
            ->assertJsonPath('data.11.id', 2012);

        $this->assertSame(range(1, 12), $openPagesRequested);
        $this->assertSame(12, DealerOrderSnapshot::query()->count());
        $this->assertTrue(
            DealerOrderSnapshot::query()
                ->where('record_key', 'north-coast-doors-91::order-2001')
                ->where('queried_page', 1)
                ->where('queried_status', 'Open')
                ->exists()
        );
    }

    public function test_my_jobs_page_returns_requested_status_page(): void
    {
        Http::fake(function (HttpRequest $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            $this->assertSame('Completed', $query['filter']['status'] ?? null);
            $this->assertSame(' ', $query['filter']['search'] ?? null);
            $this->assertSame('3', (string) ($query['page'] ?? ''));

            return Http::response([
                'data' => [
                    ['id' => 303, 'status' => 'Completed'],
                ],
                'pagination' => [
                    'current_page' => 3,
                    'total_pages' => 5,
                    'total' => 41,
                    'per_page' => 10,
                ],
            ]);
        });

        $response = $this
            ->withSession(['impersonated_token' => 'impersonated-token'])
            ->getJson(route('my.jobs.page', ['status' => 'Completed', 'page' => 3]));

        $response
            ->assertOk()
            ->assertJsonPath('selected_status', 'Completed')
            ->assertJsonPath('pagination.current_page', 3)
            ->assertJsonPath('pagination.last_page', 5)
            ->assertJsonPath('data.0.id', 303);
    }

    public function test_my_jobs_all_uses_blank_search_and_fetches_every_open_page(): void
    {
        $openPagesRequested = [];

        Http::fake(function (HttpRequest $request) use (&$openPagesRequested) {
            $this->assertSame('https://ordering-master.test', $request->header('Origin')[0] ?? null);
            $this->assertSame('Bearer impersonated-token', $request->header('Authorization')[0] ?? null);

            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            $this->assertSame('-by_date', $query['sort'] ?? null);
            $this->assertSame(' ', $query['filter']['search'] ?? null);

            $status = $query['filter']['status'] ?? null;
            $page = (int) ($query['page'] ?? 1);

            if ($status === 'Open') {
                $openPagesRequested[] = $page;

                return Http::response([
                    'data' => [
                        ['id' => 1000 + $page, 'status' => 'Open'],
                    ],
                    'pagination' => [
                        'total_pages' => 16,
                    ],
                ]);
            }

            return Http::response([
                'data' => [],
                'pagination' => [
                    'total_pages' => 1,
                ],
            ]);
        });

        $response = $this
            ->withSession(['impersonated_token' => 'impersonated-token'])
            ->getJson(route('my.jobs.all'));

        $response
            ->assertOk()
            ->assertJsonPath('total', 16)
            ->assertJsonCount(16, 'data')
            ->assertJsonPath('data.0.id', 1001)
            ->assertJsonPath('data.15.id', 1016);

        $this->assertSame(range(1, 16), $openPagesRequested);
    }

    public function test_my_leads_page_returns_requested_page(): void
    {
        Http::fake(function (HttpRequest $request) {
            $this->assertSame('https://ordering-master.test', $request->header('Origin')[0] ?? null);
            $this->assertSame('Bearer impersonated-token', $request->header('Authorization')[0] ?? null);

            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            $this->assertSame(' ', $query['filter']['search'] ?? null);
            $this->assertArrayNotHasKey('status', $query['filter'] ?? []);
            $this->assertSame('2', (string) ($query['page'] ?? ''));

            return Http::response([
                'data' => [
                    ['id' => 502, 'status' => 'Open'],
                ],
                'pagination' => [
                    'current_page' => 2,
                    'total_pages' => 4,
                    'total' => 31,
                    'per_page' => 10,
                ],
            ]);
        });

        $response = $this
            ->withSession([
                'impersonated_token' => 'impersonated-token',
                'ordering_portal_context' => [
                    'dealer_id' => 44,
                    'dealer_name' => 'Lead Dealer',
                    'user_name' => 'Lena Lead',
                    'user_email' => 'lena@example.com',
                    'source' => 'impersonation',
                ],
            ])
            ->getJson(route('my.leads.page', ['page' => 2]));

        $response
            ->assertOk()
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonPath('pagination.last_page', 4)
            ->assertJsonPath('data.0.id', 502);

        $leadSnapshot = DealerLeadSnapshot::query()->firstOrFail();

        $this->assertSame('lead-dealer-44::lead-502', $leadSnapshot->record_key);
        $this->assertSame('lead-dealer-44', $leadSnapshot->dealer_scope);
        $this->assertSame('Lead Dealer', $leadSnapshot->dealer_name);
        $this->assertSame(502, $leadSnapshot->external_lead_id);
        $this->assertSame(2, $leadSnapshot->queried_page);
    }

    public function test_my_leads_all_uses_blank_search_and_fetches_every_page(): void
    {
        $requestedPages = [];

        Http::fake(function (HttpRequest $request) use (&$requestedPages) {
            $this->assertSame('https://ordering-master.test', $request->header('Origin')[0] ?? null);
            $this->assertSame('Bearer impersonated-token', $request->header('Authorization')[0] ?? null);

            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            $this->assertSame('-by_date', $query['sort'] ?? null);
            $this->assertSame(' ', $query['filter']['search'] ?? null);
            $this->assertArrayNotHasKey('status', $query['filter'] ?? []);

            $page = (int) ($query['page'] ?? 1);
            $requestedPages[] = $page;

            return Http::response([
                'data' => [
                    ['id' => 700 + $page, 'status' => 'Open'],
                ],
                'pagination' => [
                    'total_pages' => 3,
                ],
            ]);
        });

        $response = $this
            ->withSession(['impersonated_token' => 'impersonated-token'])
            ->getJson(route('my.leads.all'));

        $response
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', 701)
            ->assertJsonPath('data.2.id', 703);

        $this->assertSame([1, 2, 3], $requestedPages);
    }

    public function test_my_work_all_returns_orders_jobs_and_leads(): void
    {
        Http::fake(function (HttpRequest $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

            $path = parse_url($request->url(), PHP_URL_PATH) ?? '';
            $status = $query['filter']['status'] ?? null;
            $page = (int) ($query['page'] ?? 1);

            if (str_ends_with($path, '/api/ordering-portal/my-orders')) {
                return Http::response([
                    'data' => $status === 'Open' && $page === 1
                        ? [['id' => 9001, 'status' => 'Open']]
                        : [],
                    'pagination' => [
                        'total_pages' => 1,
                    ],
                ]);
            }

            if (str_ends_with($path, '/api/ordering-portal/my-jobs')) {
                return Http::response([
                    'data' => $status === 'Open' && $page === 1
                        ? [['id' => 9101, 'status' => 'Open']]
                        : [],
                    'pagination' => [
                        'total_pages' => 1,
                    ],
                ]);
            }

            if (str_ends_with($path, '/api/ordering-portal/my-leads')) {
                return Http::response([
                    'data' => $page === 1
                        ? [['id' => 9201, 'status' => 'Open']]
                        : [],
                    'pagination' => [
                        'total_pages' => 1,
                    ],
                ]);
            }

            return Http::response([], 404);
        });

        $response = $this
            ->withSession(['impersonated_token' => 'impersonated-token'])
            ->getJson(route('my.work.all'));

        $response
            ->assertOk()
            ->assertJsonPath('data.orders.0.id', 9001)
            ->assertJsonPath('data.jobs.0.id', 9101)
            ->assertJsonPath('data.leads.0.id', 9201)
            ->assertJsonPath('meta.orders.total', 1)
            ->assertJsonPath('meta.jobs.total', 1)
            ->assertJsonPath('meta.leads.total', 1)
            ->assertJsonPath('errors', []);
    }

    public function test_analytics_orders_page_summarizes_staged_orders_and_leads(): void
    {
        DealerOrderSnapshot::factory()->create([
            'record_key' => 'acme-windows-77::order-9001',
            'dealer_scope' => 'acme-windows-77',
            'dealer_name' => 'Acme Windows',
            'status' => 'Open',
            'payment_status' => 'Paid',
            'total_amount' => 1500.50,
            'paid_amount' => 1500.50,
            'order_date' => '2026-04-10',
        ]);

        DealerOrderSnapshot::factory()->create([
            'record_key' => 'acme-windows-77::order-9002',
            'dealer_scope' => 'acme-windows-77',
            'dealer_name' => 'Acme Windows',
            'status' => 'Open',
            'payment_status' => 'Unpaid',
            'total_amount' => 500.00,
            'paid_amount' => 0,
            'order_date' => '2026-04-12',
        ]);

        DealerLeadSnapshot::factory()->create([
            'record_key' => 'acme-windows-77::lead-7001',
            'dealer_scope' => 'acme-windows-77',
            'dealer_name' => 'Acme Windows',
            'status' => 'Open',
            'amount' => 900.00,
            'lead_date' => '2026-04-09',
        ]);

        DealerLeadSnapshot::factory()->create([
            'record_key' => 'acme-windows-77::lead-7002',
            'dealer_scope' => 'acme-windows-77',
            'dealer_name' => 'Acme Windows',
            'status' => 'Sent',
            'amount' => 1250.00,
            'lead_date' => '2026-04-11',
        ]);

        DealerOrderSnapshot::factory()->create([
            'record_key' => 'other-dealer-55::order-8001',
            'dealer_scope' => 'other-dealer-55',
            'dealer_name' => 'Other Dealer',
            'status' => 'Completed',
            'payment_status' => 'Paid',
            'total_amount' => 999.99,
            'paid_amount' => 999.99,
            'order_date' => '2026-03-01',
        ]);

        $response = $this->get(route('analytics.orders', [
            'dealer_scope' => 'acme-windows-77',
            'status' => 'Open',
            'date_from' => '2026-04-01',
            'date_to' => '2026-04-30',
            'chart' => 'lead_status',
            'chart_metric' => 'amount',
            'chart_limit' => 2,
        ]));

        $response
            ->assertOk()
            ->assertViewHas('summary', function (array $summary): bool {
                return $summary['order_count'] === 2
                    && $summary['lead_count'] === 2
                    && round($summary['order_value'], 2) === 2000.50
                    && round($summary['paid_value'], 2) === 1500.50
                    && round($summary['avg_order_value'], 2) === 1000.25
                    && round((float) $summary['conversion_rate'], 1) === 100.0;
            })
            ->assertViewHas('dealerPerformance', function (array $dealerPerformance): bool {
                return count($dealerPerformance) === 1
                    && $dealerPerformance[0]['dealer_scope'] === 'acme-windows-77'
                    && $dealerPerformance[0]['total_orders'] === 2
                    && $dealerPerformance[0]['total_leads'] === 2;
            })
            ->assertViewHas('chartConfig', function (array $chartConfig): bool {
                return $chartConfig['selected_chart'] === 'lead_status'
                    && $chartConfig['selected_metric'] === 'amount'
                    && $chartConfig['selected_limit'] === 2
                    && $chartConfig['metric_label'] === 'Lead Amount'
                    && count($chartConfig['entries']) === 2
                    && round($chartConfig['total'], 2) === 2150.00;
            })
            ->assertSee('Orders And Leads Dashboard')
            ->assertSee('Acme Windows')
            ->assertSee('$2,000.50')
            ->assertSee('Pie Chart');
    }
}
