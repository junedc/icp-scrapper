<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DealerControllerOrderingPortalTest extends TestCase
{
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
            ->withSession(['impersonated_token' => 'impersonated-token'])
            ->getJson(route('my.orders.page', ['status' => 'Draft', 'page' => 4]));

        $response
            ->assertOk()
            ->assertJsonPath('selected_status', 'Draft')
            ->assertJsonPath('pagination.current_page', 4)
            ->assertJsonPath('pagination.last_page', 7)
            ->assertJsonPath('data.0.id', 404);
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
            ->withSession(['impersonated_token' => 'impersonated-token'])
            ->getJson(route('my.orders.all'));

        $response
            ->assertOk()
            ->assertJsonPath('total', 12)
            ->assertJsonCount(12, 'data')
            ->assertJsonPath('data.0.id', 2001)
            ->assertJsonPath('data.11.id', 2012);

        $this->assertSame(range(1, 12), $openPagesRequested);
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
            ->withSession(['impersonated_token' => 'impersonated-token'])
            ->getJson(route('my.leads.page', ['page' => 2]));

        $response
            ->assertOk()
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonPath('pagination.last_page', 4)
            ->assertJsonPath('data.0.id', 502);
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
}
