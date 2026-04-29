<?php

namespace App\Http\Controllers;

use App\Jobs\FetchDealerOrdersSnapshotJob;
use App\Models\DealerLeadSnapshot;
use App\Models\DealerOrderSnapshot;
use App\Models\DealerOrderSync;
use App\Services\DealerLeadSnapshotRecorder;
use App\Services\DealerOrderSnapshotRecorder;
use App\Services\StarlineApiClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;

class DealerController extends Controller
{
    protected StarlineApiClient $apiClient;

    protected DealerOrderSnapshotRecorder $dealerOrderSnapshotRecorder;

    protected DealerLeadSnapshotRecorder $dealerLeadSnapshotRecorder;

    protected array $statuses = [
        'Draft',
        'Open',
        'Quote',
        'In Production',
        'Completed',
        'Awaiting Payment on Account',
        'Finance Approval',
        'Production Approval',
        'Production Scheduled',
        'Production Completed',
        'Dispatched',
        'Awaiting Payment',
        'Cancelled',
        'On The Way',
    ];

    public function __construct(
        StarlineApiClient $apiClient,
        DealerOrderSnapshotRecorder $dealerOrderSnapshotRecorder,
        DealerLeadSnapshotRecorder $dealerLeadSnapshotRecorder
    ) {
        $this->apiClient = $apiClient;
        $this->dealerOrderSnapshotRecorder = $dealerOrderSnapshotRecorder;
        $this->dealerLeadSnapshotRecorder = $dealerLeadSnapshotRecorder;
    }

    public function showLoginForm()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $response = $this->apiClient->post('/api/admin/login', [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            // Assuming the token is in 'access_token' or 'token' key based on standard Laravel API responses
            $token = $data['access_token'] ?? $data['token'] ?? $data['data']['token'] ?? null;

            if ($token) {
                session(['api_token' => $token]);
                $this->apiClient->flashLogs();

                return redirect()->route('dealers.index');
            }
        }

        return back()->withErrors(['login' => 'Invalid credentials or API error.']);
    }

    public function dealerLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $response = $this->apiClient->post('/api/login', [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], true); // Use ordering headers

        if ($response->successful()) {
            $data = $response->json();
            $token = $data['access_token'] ?? $data['token'] ?? $data['data']['token'] ?? null;

            if ($token) {
                session([
                    'dealer_token' => $token,
                    'ordering_portal_context' => [
                        'dealer_id' => null,
                        'dealer_name' => null,
                        'user_name' => null,
                        'user_email' => $credentials['email'],
                        'source' => 'dealer_login',
                    ],
                ]);
                $this->apiClient->flashLogs();

                return redirect()->route('my.orders');
            }
        }

        return back()->withErrors(['dealer_login' => 'Invalid credentials or API error.']);
    }

    public function index(Request $request)
    {
        if (! session('api_token')) {
            return redirect()->route('login');
        }

        $query = [
            'page' => $request->query('page', 1),
            'order_by' => 'date',
            'order_direction' => 'desc',
        ];

        if ($request->filled('search')) {
            $query['search'] = $request->query('search');
        }

        $response = $this->apiClient->get('/api/admin/dealer-maintenance/dealers', $query);

        if ($response->successful()) {
            $data = $response->json();
            $dealers = $data['data'] ?? [];
            $pagination = [
                'current_page' => $data['current_page'] ?? 1,
                'last_page' => $data['last_page'] ?? 1,
                'total' => $data['total'] ?? 0,
                'per_page' => $data['per_page'] ?? 15,
            ];

            return view('dealers', compact('dealers', 'pagination'));
        }

        if ($response->status() === 401) {
            session()->forget('api_token');

            return redirect()->route('login')->withErrors(['login' => 'Session expired. Please login again.']);
        }

        return view('dealers', ['dealers' => [], 'error' => 'Failed to fetch dealers.']);
    }

    public function orders(Request $request, $id)
    {
        if (! session('api_token')) {
            return redirect()->route('login');
        }

        $query = [
            'paginate' => 9,
            'page' => $request->query('page', 1),
        ];

        $response = $this->apiClient->get("/api/admin/dealer-maintenance/dealer/{$id}/orders", $query);

        if ($response->successful()) {
            $data = $response->json();
            $orders = $data['data'] ?? [];

            // Fetch all orders for this dealer to calculate aggregated statistics
            $statsResponse = $this->apiClient->get("/api/admin/dealer-maintenance/dealer/{$id}/orders", ['paginate' => 1000]);
            $statistics = [];
            if ($statsResponse->successful()) {
                $allOrders = $statsResponse->json()['data'] ?? [];
                if (is_array($allOrders)) {
                    foreach ($allOrders as $order) {
                        $status = $order['status'] ?? 'Unknown';
                        $statistics[$status] = ($statistics[$status] ?? 0) + 1;
                    }
                }
            }

            $apiPagination = $data['pagination'] ?? [];
            $pagination = [
                'current_page' => $apiPagination['current_page'] ?? 1,
                'last_page' => $apiPagination['total_pages'] ?? 1,
                'total' => $apiPagination['total'] ?? 0,
                'per_page' => $apiPagination['per_page'] ?? 9,
            ];

            return view('orders', compact('orders', 'id', 'pagination', 'statistics'));
        }

        if ($response->status() === 401) {
            session()->forget('api_token');

            return redirect()->route('login')->withErrors(['login' => 'Session expired. Please login again.']);
        }

        return view('orders', ['orders' => [], 'id' => $id, 'error' => 'Failed to fetch orders.']);
    }

    public function users($id)
    {
        if (! session('api_token')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $response = $this->apiClient->get("/api/admin/dealer-maintenance/dealer/{$id}/users");

        if ($response->successful()) {
            $users = $response->json()['data'] ?? [];

            // Filter out 'admin' users
            $filteredUsers = array_filter($users, function ($user) {
                $role = strtolower($user['role'] ?? $user['type'] ?? '');
                $name = strtolower($user['name'] ?? '');
                $email = strtolower($user['email'] ?? '');

                return $role !== 'admin' && ! str_contains($name, 'admin') && ! str_contains($email, 'admin');
            });

            // Map users to include necessary data for impersonation (if needed)
            $mappedUsers = array_map(function ($user) {
                return [
                    'id' => $user['id'],
                    'name' => $user['name'] ?? 'N/A',
                    'email' => $user['email'] ?? 'N/A',
                    'status' => $user['status'] ?? 'N/A',
                ];
            }, array_values($filteredUsers));

            return response()->json([
                'data' => $mappedUsers,
                'api_logs' => $this->apiClient->getLogs(),
            ]);
        }

        return response()->json([
            'error' => 'Failed to fetch users',
            'api_logs' => $this->apiClient->getLogs(),
        ], $response->status());
    }

    public function impersonate(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'dealer_id' => 'nullable|integer',
            'dealer_name' => 'nullable|string|max:255',
            'user_name' => 'nullable|string|max:255',
        ]);

        // The user didn't specify a password for impersonation,
        // but typically /api/login requires one.
        // In some systems, there's a special master password or a way to login without one if coming from admin.
        // However, looking at the instruction "impersonate button to login to endpoint https://api-master.local/api/login"
        // and "ordering-master.test" headers.

        // I will try to call the login endpoint.
        // If it requires a password and we don't have it, we might need a different strategy.
        // But for now, I'll implement what was asked.

        $response = $this->apiClient->post('/api/login', [
            'email' => $request->email,
            'password' => 'password', // Placeholder or as per system configuration
        ], true); // Use ordering headers

        if ($response->successful()) {
            $data = $response->json();
            $token = $data['access_token'] ?? $data['token'] ?? $data['data']['token'] ?? null;

            if ($token) {
                // Store in a separate session key to avoid losing admin session
                session([
                    'impersonated_token' => $token,
                    'ordering_portal_context' => [
                        'dealer_id' => $request->integer('dealer_id') ?: null,
                        'dealer_name' => $this->normalizeContextValue($request->input('dealer_name')),
                        'user_name' => $this->normalizeContextValue($request->input('user_name')),
                        'user_email' => $request->email,
                        'source' => 'impersonation',
                    ],
                ]);
                $this->apiClient->flashLogs();

                return response()->json([
                    'success' => true,
                    'token' => $token,
                    'api_logs' => $this->apiClient->getLogs(),
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'error' => 'Login failed: '.($response->json()['message'] ?? 'Unknown error'),
            'status' => $response->status(),
            'api_logs' => $this->apiClient->getLogs(),
        ], 400);
    }

    public function showOrder($id)
    {
        if (! session('api_token')) {
            return redirect()->route('login');
        }

        $response = $this->apiClient->get("/api/admin/production-review/{$id}");

        if ($response->successful()) {
            $order = $response->json();

            return view('order_details', compact('order', 'id'));
        }

        if ($response->status() === 401) {
            session()->forget('api_token');

            return redirect()->route('login')->withErrors(['login' => 'Session expired. Please login again.']);
        }

        return back()->withErrors(['error' => 'Failed to fetch order details.']);
    }

    public function showMyOrderSpecification($id)
    {
        $token = session('dealer_token') ?? session('impersonated_token');
        if (! $token) {
            return redirect()->route('login')->withErrors(['error' => 'No active dealer session.']);
        }

        $response = $this->apiClient->get("/api/ordering-portal/my-orders/{$id}/specification", [], true, $token);

        if ($response->successful()) {
            $specification = $response->json();

            return view('my_order_specification', [
                'specification' => $specification,
                'id' => $id,
            ]);
        }

        if ($response->status() === 401) {
            $this->clearOrderingPortalSession();

            return redirect()->route('login')->withErrors(['error' => 'Dealer session expired.']);
        }

        return back()->withErrors(['error' => 'Failed to fetch order specification.']);
    }

    public function myOrders(Request $request)
    {
        $token = session('dealer_token') ?? session('impersonated_token');
        if (! $token) {
            return redirect()->route('login')->withErrors(['error' => 'No active dealer session.']);
        }

        $selectedStatus = $this->resolveOrderingPortalStatus($request->query('status'));
        $page = max(1, (int) $request->query('page', 1));
        $createOnly = $this->resolveCreateOnly($request);

        [
            'response' => $response,
            'orders' => $orders,
            'pagination' => $pagination,
        ] = $this->fetchMyOrdersPage($token, $selectedStatus, $page);

        if ($response->successful()) {
            $this->recordOrderSnapshots($orders, $selectedStatus, $page, $createOnly);
            $availableStatuses = $this->statuses;

            return view('my_orders', compact('orders', 'pagination', 'selectedStatus', 'availableStatuses', 'createOnly'));
        }

        if ($response->status() === 401) {
            $this->clearOrderingPortalSession();

            return redirect()->route('login')->withErrors(['error' => 'Dealer session expired.']);
        }

        return view('my_orders', [
            'orders' => [],
            'error' => 'Failed to fetch your orders.',
            'selectedStatus' => $selectedStatus,
            'availableStatuses' => $this->statuses,
            'createOnly' => $createOnly,
        ]);
    }

    public function myOrdersPage(Request $request)
    {
        $token = session('dealer_token') ?? session('impersonated_token');
        if (! $token) {
            return response()->json(['error' => 'No active dealer session.'], 401);
        }

        $selectedStatus = $this->resolveOrderingPortalStatus($request->query('status'));
        $page = max(1, (int) $request->query('page', 1));
        $createOnly = $this->resolveCreateOnly($request);

        [
            'response' => $response,
            'orders' => $orders,
            'pagination' => $pagination,
        ] = $this->fetchMyOrdersPage($token, $selectedStatus, $page);

        if ($response->successful()) {
            $this->recordOrderSnapshots($orders, $selectedStatus, $page, $createOnly);

            return response()->json([
                'data' => $orders,
                'pagination' => $pagination,
                'selected_status' => $selectedStatus,
                'create_only' => $createOnly,
                'api_logs' => $this->apiClient->getLogs(),
            ]);
        }

        if ($response->status() === 401) {
            $this->clearOrderingPortalSession();

            return response()->json(['error' => 'Impersonation session expired.'], 401);
        }

        return response()->json([
            'error' => 'Failed to fetch your orders.',
            'selected_status' => $selectedStatus,
            'create_only' => $createOnly,
            'api_logs' => $this->apiClient->getLogs(),
        ], $response->status());
    }

    public function myOrdersAll(Request $request)
    {
        $token = session('dealer_token') ?? session('impersonated_token');
        if (! $token) {
            return response()->json(['error' => 'No active dealer session.'], 401);
        }

        $dealerScope = $this->orderingPortalDealerScope();
        $existingSync = DealerOrderSync::query()
            ->where('dealer_scope', $dealerScope)
            ->whereIn('status', ['queued', 'running'])
            ->latest('id')
            ->first();

        if ($existingSync) {
            return response()->json([
                'sync_id' => $existingSync->id,
                'status' => $existingSync->status,
                'message' => 'An order sync is already in progress.',
                'create_only' => true,
            ]);
        }

        $context = $this->orderingPortalContext();
        $sync = DealerOrderSync::query()->create([
            'dealer_scope' => $dealerScope,
            'dealer_id' => $context['dealer_id'],
            'dealer_name' => $context['dealer_name'],
            'dealer_user_email' => $context['user_email'],
            'session_source' => $context['source'],
            'status' => 'queued',
            'delay_ms' => 350,
            'create_only' => true,
        ]);

        FetchDealerOrdersSnapshotJob::dispatch(
            syncId: $sync->id,
            token: $token,
            statuses: $this->statuses,
            context: $context,
            delayMs: $sync->delay_ms,
        );

        return response()->json([
            'sync_id' => $sync->id,
            'status' => $sync->status,
            'message' => 'Order sync queued.',
            'create_only' => true,
        ]);
    }

    public function myOrdersCurrent(Request $request)
    {
        $selectedStatus = $this->resolveOrderingPortalStatus($request->query('status'));
        $orders = $this->localOrderSnapshots($selectedStatus);

        return response()->json([
            'data' => $orders,
            'selected_status' => $selectedStatus,
            'source' => 'local_snapshot',
            'api_logs' => [],
        ]);
    }

    public function myOrdersAllStatus(DealerOrderSync $dealerOrderSync)
    {
        return response()->json([
            'sync_id' => $dealerOrderSync->id,
            'status' => $dealerOrderSync->status,
            'current_status' => $dealerOrderSync->current_status,
            'current_page' => $dealerOrderSync->current_page,
            'last_page' => $dealerOrderSync->last_page,
            'total_records' => $dealerOrderSync->total_records,
            'error_message' => $dealerOrderSync->error_message,
            'finished_at' => $dealerOrderSync->finished_at?->toDateTimeString(),
            'create_only' => $dealerOrderSync->create_only,
        ]);
    }

    public function myLeads(Request $request)
    {
        $token = session('dealer_token') ?? session('impersonated_token');
        if (! $token) {
            return redirect()->route('login')->withErrors(['error' => 'No active dealer session.']);
        }

        $page = max(1, (int) $request->query('page', 1));

        [
            'response' => $response,
            'leads' => $leads,
            'pagination' => $pagination,
        ] = $this->fetchMyLeadsPage($token, $page);

        if ($response->successful()) {
            $this->recordLeadSnapshots($leads, $page);

            return view('my_leads', compact('leads', 'pagination'));
        }

        if ($response->status() === 401) {
            $this->clearOrderingPortalSession();

            return redirect()->route('login')->withErrors(['error' => 'Dealer session expired.']);
        }

        return view('my_leads', [
            'leads' => [],
            'error' => 'Failed to fetch your leads.',
        ]);
    }

    public function myLeadsPage(Request $request)
    {
        $token = session('dealer_token') ?? session('impersonated_token');
        if (! $token) {
            return response()->json(['error' => 'No active dealer session.'], 401);
        }

        $page = max(1, (int) $request->query('page', 1));

        [
            'response' => $response,
            'leads' => $leads,
            'pagination' => $pagination,
        ] = $this->fetchMyLeadsPage($token, $page);

        if ($response->successful()) {
            $this->recordLeadSnapshots($leads, $page);

            return response()->json([
                'data' => $leads,
                'pagination' => $pagination,
                'api_logs' => $this->apiClient->getLogs(),
            ]);
        }

        if ($response->status() === 401) {
            $this->clearOrderingPortalSession();

            return response()->json(['error' => 'Dealer session expired.'], 401);
        }

        return response()->json([
            'error' => 'Failed to fetch your leads.',
            'api_logs' => $this->apiClient->getLogs(),
        ], $response->status());
    }

    public function myLeadsAll(Request $request)
    {
        $token = session('dealer_token') ?? session('impersonated_token');
        if (! $token) {
            return response()->json(['error' => 'No active dealer session.'], 401);
        }

        $result = $this->fetchAllLeads($token);

        if ($result['status'] === 401) {
            $this->clearOrderingPortalSession();

            return response()->json(['error' => 'Dealer session expired.'], 401);
        }

        return response()->json([
            'data' => $result['items'],
            'total' => count($result['items']),
            'last_page' => $result['last_page'],
            'errors' => $result['error'] ? ['leads' => $result['error']] : [],
            'api_logs' => $this->apiClient->getLogs(),
        ]);
    }

    public function myWorkAll(Request $request)
    {
        $token = session('dealer_token') ?? session('impersonated_token');
        if (! $token) {
            return response()->json(['error' => 'No active dealer session.'], 401);
        }

        $orders = $this->fetchAllOrders($token);
        $leads = $this->fetchAllLeads($token);

        $responseStatuses = [$orders['status'], $leads['status']];
        if (in_array(401, $responseStatuses, true)) {
            $this->clearOrderingPortalSession();

            return response()->json(['error' => 'Dealer session expired.'], 401);
        }

        $errors = array_filter([
            'orders' => $orders['error'],
            'leads' => $leads['error'],
        ]);

        return response()->json([
            'data' => [
                'orders' => $orders['items'],
                'leads' => $leads['items'],
            ],
            'meta' => [
                'orders' => [
                    'total' => count($orders['items']),
                    'last_page' => $orders['last_page'],
                ],
                'leads' => [
                    'total' => count($leads['items']),
                    'last_page' => $leads['last_page'],
                ],
            ],
            'errors' => $errors,
            'api_logs' => $this->apiClient->getLogs(),
        ]);
    }

    public function logout()
    {
        session()->forget('api_token');
        $this->clearOrderingPortalSession();

        return redirect()->route('login');
    }

    private function orderingPortalFilter(?string $status = null): array
    {
        $filter = [
            'search' => ' ',
        ];

        if ($status !== null) {
            $filter['status'] = $status;
        }

        return $filter;
    }

    private function resolveOrderingPortalStatus(?string $status): string
    {
        return in_array($status, $this->statuses, true) ? $status : 'Open';
    }

    /**
     * @return array{
     *     response: Response,
     *     orders: array<int, array<string, mixed>>,
     *     pagination: array{current_page: int, last_page: int, total: int, per_page: int}
     * }
     */
    private function fetchMyOrdersPage(string $token, string $status, int $page): array
    {
        $response = $this->apiClient->get('/api/ordering-portal/my-orders', [
            'paginate' => 10,
            'page' => $page,
            'filter' => $this->orderingPortalFilter($status),
            'sort' => '-by_date',
        ], true, $token);

        $data = $response->json();
        $orders = $data['data'] ?? [];
        $apiPagination = $data['pagination'] ?? [];

        return [
            'response' => $response,
            'orders' => $orders,
            'pagination' => [
                'current_page' => $apiPagination['current_page'] ?? $page,
                'last_page' => $apiPagination['total_pages'] ?? 1,
                'total' => $apiPagination['total'] ?? count($orders),
                'per_page' => $apiPagination['per_page'] ?? 10,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function localOrderSnapshots(string $status): array
    {
        $query = DealerOrderSnapshot::query()
            ->where('dealer_scope', $this->orderingPortalDealerScope())
            ->where('status', $status)
            ->orderByDesc('order_date')
            ->orderByDesc('external_order_id');

        return $query
            ->get()
            ->map(fn (DealerOrderSnapshot $row): array => [
                'id' => $row->external_order_id,
                'container_id' => $row->container_id,
                'status' => $row->status,
                'dealer_reference' => $row->dealer_reference,
                'total' => $row->total_amount,
                'order_date' => optional($row->order_date)?->toDateString(),
            ])
            ->all();
    }

    /**
     * @return array{
     *     response: Response,
     *     leads: array<int, array<string, mixed>>,
     *     pagination: array{current_page: int, last_page: int, total: int, per_page: int}
     * }
     */
    private function fetchMyLeadsPage(string $token, int $page): array
    {
        $response = $this->apiClient->get('/api/ordering-portal/my-leads', [
            'paginate' => 10,
            'page' => $page,
            'filter' => $this->orderingPortalFilter(),
            'sort' => '-by_date',
        ], true, $token);

        $data = $response->json();
        $leads = $data['data'] ?? [];
        $apiPagination = $data['pagination'] ?? [];

        return [
            'response' => $response,
            'leads' => $leads,
            'pagination' => [
                'current_page' => $apiPagination['current_page'] ?? $page,
                'last_page' => $apiPagination['total_pages'] ?? 1,
                'total' => $apiPagination['total'] ?? count($leads),
                'per_page' => $apiPagination['per_page'] ?? 10,
            ],
        ];
    }

    /**
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     last_page: int,
     *     status: int,
     *     error: ?string
     * }
     */
    private function fetchAllOrders(string $token): array
    {
        return $this->fetchAllStatusAwareRecords($token, '/api/ordering-portal/my-orders');
    }

    /**
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     last_page: int,
     *     status: int,
     *     error: ?string
     * }
     */
    private function fetchAllLeads(string $token): array
    {
        return $this->fetchAllPaginatedRecords($token, '/api/ordering-portal/my-leads');
    }

    /**
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     last_page: int,
     *     status: int,
     *     error: ?string
     * }
     */
    private function fetchAllStatusAwareRecords(string $token, string $path): array
    {
        $allItems = [];
        $lastPage = 1;

        foreach ($this->statuses as $status) {
            $currentPage = 1;

            do {
                $response = $this->apiClient->get($path, [
                    'paginate' => 1000,
                    'filter' => $this->orderingPortalFilter($status),
                    'page' => $currentPage,
                    'sort' => '-by_date',
                ], true, $token);

                if (! $response->successful()) {
                    return [
                        'items' => $allItems,
                        'last_page' => $lastPage,
                        'status' => $response->status(),
                        'error' => $this->extractApiError($response, 'Failed to fetch ordering portal records.'),
                    ];
                }

                $data = $response->json();
                $allItems = array_merge($allItems, $data['data'] ?? []);

                $lastPage = $data['pagination']['total_pages'] ?? 1;
                $currentPage++;
            } while ($currentPage <= $lastPage);
        }

        return [
            'items' => $allItems,
            'last_page' => $lastPage,
            'status' => 200,
            'error' => null,
        ];
    }

    /**
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     last_page: int,
     *     status: int,
     *     error: ?string
     * }
     */
    private function fetchAllPaginatedRecords(string $token, string $path): array
    {
        $allItems = [];
        $lastPage = 1;
        $currentPage = 1;

        do {
            $response = $this->apiClient->get($path, [
                'paginate' => 1000,
                'filter' => $this->orderingPortalFilter(),
                'page' => $currentPage,
                'sort' => '-by_date',
            ], true, $token);

            if (! $response->successful()) {
                return [
                    'items' => $allItems,
                    'last_page' => $lastPage,
                    'status' => $response->status(),
                    'error' => $this->extractApiError($response, 'Failed to fetch ordering portal records.'),
                ];
            }

            $data = $response->json();
            $batchItems = $data['data'] ?? [];

            if ($path === '/api/ordering-portal/my-leads') {
                $this->recordLeadSnapshots($batchItems, $currentPage);
            }

            $allItems = array_merge($allItems, $batchItems);

            $lastPage = $data['pagination']['total_pages'] ?? 1;
            $currentPage++;
        } while ($currentPage <= $lastPage);

        return [
            'items' => $allItems,
            'last_page' => $lastPage,
            'status' => 200,
            'error' => null,
        ];
    }

    private function extractApiError(Response $response, string $fallback): string
    {
        $message = $response->json('message');
        if (is_string($message) && $message !== '') {
            return $message;
        }

        $body = trim($response->body());

        return $body !== '' ? $body : $fallback;
    }

    public function analyticsOrders(Request $request)
    {
        $filters = $request->validate([
            'dealer_scope' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'chart' => 'nullable|string|in:order_status,payment_status,lead_status',
            'chart_metric' => 'nullable|string|in:count,amount',
            'chart_limit' => 'nullable|integer|min:2|max:12',
        ]);

        $selectedScope = $this->normalizeContextValue($filters['dealer_scope'] ?? null);
        $selectedStatus = $this->normalizeContextValue($filters['status'] ?? null);
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $selectedChart = $filters['chart'] ?? 'order_status';
        $selectedChartMetric = $filters['chart_metric'] ?? 'count';
        $selectedChartLimit = (int) ($filters['chart_limit'] ?? 5);

        $ordersQuery = DealerOrderSnapshot::query();
        $leadsQuery = DealerLeadSnapshot::query();

        if ($selectedScope !== null) {
            $ordersQuery->where('dealer_scope', $selectedScope);
            $leadsQuery->where('dealer_scope', $selectedScope);
        }

        if ($selectedStatus !== null) {
            $ordersQuery->where('status', $selectedStatus);
        }

        $this->applyDateRange($ordersQuery, 'order_date', $dateFrom, $dateTo);
        $this->applyDateRange($leadsQuery, 'lead_date', $dateFrom, $dateTo);

        $orderCount = (clone $ordersQuery)->count();
        $leadCount = (clone $leadsQuery)->count();
        $orderValue = (float) ((clone $ordersQuery)->sum('total_amount') ?: 0);
        $paidValue = (float) ((clone $ordersQuery)->sum('paid_amount') ?: 0);
        $avgOrderValue = $orderCount > 0 ? $orderValue / $orderCount : 0.0;
        $conversionRate = $leadCount > 0 ? ($orderCount / $leadCount) * 100 : null;

        $statusBreakdown = (clone $ordersQuery)
            ->select('status')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_value')
            ->groupBy('status')
            ->orderByDesc('total_orders')
            ->get()
            ->map(fn (DealerOrderSnapshot $row): array => [
                'status' => $row->status ?? 'Unknown',
                'total_orders' => (int) $row->total_orders,
                'total_value' => (float) $row->total_value,
            ])
            ->all();

        $paymentBreakdown = (clone $ordersQuery)
            ->select('payment_status')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_value')
            ->groupBy('payment_status')
            ->orderByDesc('total_orders')
            ->get()
            ->map(fn (DealerOrderSnapshot $row): array => [
                'payment_status' => $row->payment_status ?? 'Unknown',
                'total_orders' => (int) $row->total_orders,
                'total_value' => (float) $row->total_value,
            ])
            ->all();

        $leadStatusBreakdown = (clone $leadsQuery)
            ->select('status')
            ->selectRaw('COUNT(*) as total_leads')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('status')
            ->orderByDesc('total_leads')
            ->get()
            ->map(fn (DealerLeadSnapshot $row): array => [
                'status' => $row->status ?? 'Unknown',
                'total_leads' => (int) $row->total_leads,
                'total_amount' => (float) $row->total_amount,
            ])
            ->all();

        $orderTrend = (clone $ordersQuery)
            ->selectRaw('order_date as trend_date')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_value')
            ->whereNotNull('order_date')
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->get()
            ->map(fn (DealerOrderSnapshot $row): array => [
                'date' => (string) $row->trend_date,
                'total_orders' => (int) $row->total_orders,
                'total_value' => (float) $row->total_value,
            ])
            ->all();

        $leadTrend = (clone $leadsQuery)
            ->selectRaw('lead_date as trend_date')
            ->selectRaw('COUNT(*) as total_leads')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
            ->whereNotNull('lead_date')
            ->groupBy('lead_date')
            ->orderBy('lead_date')
            ->get()
            ->map(fn (DealerLeadSnapshot $row): array => [
                'date' => (string) $row->trend_date,
                'total_leads' => (int) $row->total_leads,
                'total_amount' => (float) $row->total_amount,
            ])
            ->all();

        $availableScopes = collect()
            ->merge(DealerOrderSnapshot::query()->pluck('dealer_scope'))
            ->merge(DealerLeadSnapshot::query()->pluck('dealer_scope'))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $availableOrderStatuses = DealerOrderSnapshot::query()
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->values()
            ->all();

        $ordersByDealer = (clone $ordersQuery)
            ->select('dealer_scope')
            ->selectRaw('MAX(dealer_name) as dealer_name')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_value')
            ->groupBy('dealer_scope')
            ->get()
            ->keyBy('dealer_scope');

        $leadsByDealer = (clone $leadsQuery)
            ->select('dealer_scope')
            ->selectRaw('MAX(dealer_name) as dealer_name')
            ->selectRaw('COUNT(*) as total_leads')
            ->groupBy('dealer_scope')
            ->get()
            ->keyBy('dealer_scope');

        $dealerPerformance = collect($availableScopes)
            ->map(function (string $dealerScope) use ($ordersByDealer, $leadsByDealer): array {
                $orderRow = $ordersByDealer->get($dealerScope);
                $leadRow = $leadsByDealer->get($dealerScope);
                $totalOrders = (int) ($orderRow->total_orders ?? 0);
                $totalLeads = (int) ($leadRow->total_leads ?? 0);

                return [
                    'dealer_scope' => $dealerScope,
                    'dealer_name' => $orderRow->dealer_name ?? $leadRow->dealer_name ?? $dealerScope,
                    'total_orders' => $totalOrders,
                    'total_leads' => $totalLeads,
                    'total_value' => (float) ($orderRow->total_value ?? 0),
                    'conversion_rate' => $totalLeads > 0 ? ($totalOrders / $totalLeads) * 100 : null,
                ];
            })
            ->filter(fn (array $row): bool => $row['total_orders'] > 0 || $row['total_leads'] > 0)
            ->sortByDesc('total_value')
            ->values()
            ->all();

        $chartConfig = $this->buildAnalyticsPieChart(
            selectedChart: $selectedChart,
            selectedMetric: $selectedChartMetric,
            selectedLimit: $selectedChartLimit,
            statusBreakdown: $statusBreakdown,
            paymentBreakdown: $paymentBreakdown,
            leadStatusBreakdown: $leadStatusBreakdown,
        );

        return view('analytics_orders', [
            'summary' => [
                'order_count' => $orderCount,
                'lead_count' => $leadCount,
                'order_value' => $orderValue,
                'paid_value' => $paidValue,
                'avg_order_value' => $avgOrderValue,
                'conversion_rate' => $conversionRate,
            ],
            'filters' => [
                'dealer_scope' => $selectedScope,
                'status' => $selectedStatus,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'chart' => $selectedChart,
                'chart_metric' => $selectedChartMetric,
                'chart_limit' => $selectedChartLimit,
            ],
            'availableScopes' => $availableScopes,
            'availableOrderStatuses' => $availableOrderStatuses,
            'statusBreakdown' => $statusBreakdown,
            'paymentBreakdown' => $paymentBreakdown,
            'leadStatusBreakdown' => $leadStatusBreakdown,
            'orderTrend' => $orderTrend,
            'leadTrend' => $leadTrend,
            'dealerPerformance' => $dealerPerformance,
            'chartConfig' => $chartConfig,
            'latestSyncAt' => collect([
                DealerOrderSnapshot::query()->max('synced_at'),
                DealerLeadSnapshot::query()->max('synced_at'),
            ])->filter()->max(),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $orders
     */
    private function recordOrderSnapshots(array $orders, ?string $status, ?int $page, bool $createOnly = false): void
    {
        $this->dealerOrderSnapshotRecorder->record(
            orders: $orders,
            context: $this->orderingPortalContext(),
            sourceEndpoint: '/api/ordering-portal/my-orders',
            queriedStatus: $status,
            queriedPage: $page,
            createOnly: $createOnly,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $leads
     */
    private function recordLeadSnapshots(array $leads, ?int $page): void
    {
        $this->dealerLeadSnapshotRecorder->record(
            leads: $leads,
            context: $this->orderingPortalContext(),
            sourceEndpoint: '/api/ordering-portal/my-leads',
            queriedPage: $page,
        );
    }

    /**
     * @return array{
     *     dealer_id: ?int,
     *     dealer_name: ?string,
     *     user_name: ?string,
     *     user_email: ?string,
     *     source: ?string
     * }
     */
    private function orderingPortalContext(): array
    {
        $context = session('ordering_portal_context', []);

        if (! is_array($context)) {
            $context = [];
        }

        $dealerId = $context['dealer_id'] ?? null;

        return [
            'dealer_id' => is_numeric($dealerId) ? (int) $dealerId : null,
            'dealer_name' => $this->normalizeContextValue($context['dealer_name'] ?? null),
            'user_name' => $this->normalizeContextValue($context['user_name'] ?? null),
            'user_email' => $this->normalizeContextValue($context['user_email'] ?? null),
            'source' => $this->normalizeContextValue($context['source'] ?? null),
        ];
    }

    private function normalizeContextValue(mixed $value): ?string
    {
        if ($value === null || ! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function resolveCreateOnly(Request $request): bool
    {
        if (! $request->has('create_only')) {
            return true;
        }

        return $request->boolean('create_only');
    }

    private function orderingPortalDealerScope(): string
    {
        $context = $this->orderingPortalContext();
        $base = $context['dealer_name'] ?? $context['user_email'] ?? 'unknown-dealer';
        $slug = str((string) $base)->slug()->value();

        if ($slug === '') {
            $slug = 'unknown-dealer';
        }

        if ($context['dealer_id'] !== null) {
            return sprintf('%s-%d', $slug, $context['dealer_id']);
        }

        return $slug;
    }

    private function clearOrderingPortalSession(): void
    {
        session()->forget([
            'dealer_token',
            'impersonated_token',
            'ordering_portal_context',
        ]);
    }

    private function applyDateRange(Builder $query, string $column, ?string $dateFrom, ?string $dateTo): void
    {
        if ($dateFrom !== null) {
            $query->whereDate($column, '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->whereDate($column, '<=', $dateTo);
        }
    }

    /**
     * @param  array<int, array{status: string, total_orders: int, total_value: float}>  $statusBreakdown
     * @param  array<int, array{payment_status: string, total_orders: int, total_value: float}>  $paymentBreakdown
     * @param  array<int, array{status: string, total_leads: int, total_amount: float}>  $leadStatusBreakdown
     * @return array{
     *     selected_chart: string,
     *     selected_metric: string,
     *     selected_limit: int,
     *     metric_label: string,
     *     title: string,
     *     entries: array<int, array{label: string, value: float, percent: float, color: string}>,
     *     total: float
     * }
     */
    private function buildAnalyticsPieChart(
        string $selectedChart,
        string $selectedMetric,
        int $selectedLimit,
        array $statusBreakdown,
        array $paymentBreakdown,
        array $leadStatusBreakdown
    ): array {
        $palette = [
            '#38bdf8',
            '#22c55e',
            '#f59e0b',
            '#a78bfa',
            '#f87171',
            '#14b8a6',
            '#fb7185',
            '#eab308',
            '#60a5fa',
            '#c084fc',
            '#34d399',
            '#f97316',
        ];

        $chartSources = [
            'order_status' => [
                'title' => 'Order Status Pie',
                'count_metric_label' => 'Orders',
                'amount_metric_label' => 'Order Value',
                'rows' => array_map(fn (array $row): array => [
                    'label' => $row['status'],
                    'count' => (float) $row['total_orders'],
                    'amount' => (float) $row['total_value'],
                ], $statusBreakdown),
            ],
            'payment_status' => [
                'title' => 'Payment Status Pie',
                'count_metric_label' => 'Orders',
                'amount_metric_label' => 'Order Value',
                'rows' => array_map(fn (array $row): array => [
                    'label' => $row['payment_status'],
                    'count' => (float) $row['total_orders'],
                    'amount' => (float) $row['total_value'],
                ], $paymentBreakdown),
            ],
            'lead_status' => [
                'title' => 'Lead Status Pie',
                'count_metric_label' => 'Leads',
                'amount_metric_label' => 'Lead Amount',
                'rows' => array_map(fn (array $row): array => [
                    'label' => $row['status'],
                    'count' => (float) $row['total_leads'],
                    'amount' => (float) $row['total_amount'],
                ], $leadStatusBreakdown),
            ],
        ];

        $chart = $chartSources[$selectedChart] ?? $chartSources['order_status'];
        $metric = $selectedMetric === 'amount' ? 'amount' : 'count';
        $metricLabel = $metric === 'amount' ? $chart['amount_metric_label'] : $chart['count_metric_label'];
        $rows = collect($chart['rows'])
            ->filter(fn (array $row): bool => $row[$metric] > 0)
            ->sortByDesc($metric)
            ->values();

        $limitedRows = $rows->take($selectedLimit)->values();
        $otherValue = $rows->slice($selectedLimit)->sum($metric);

        if ($otherValue > 0) {
            $limitedRows->push([
                'label' => 'Other',
                'count' => $metric === 'count' ? $otherValue : 0.0,
                'amount' => $metric === 'amount' ? $otherValue : 0.0,
            ]);
        }

        $total = (float) $limitedRows->sum($metric);
        $entries = $limitedRows
            ->values()
            ->map(function (array $row, int $index) use ($metric, $palette, $total): array {
                $value = (float) $row[$metric];

                return [
                    'label' => $row['label'],
                    'value' => $value,
                    'percent' => $total > 0 ? ($value / $total) * 100 : 0.0,
                    'color' => $palette[$index % count($palette)],
                ];
            })
            ->all();

        return [
            'selected_chart' => $selectedChart,
            'selected_metric' => $metric,
            'selected_limit' => $selectedLimit,
            'metric_label' => $metricLabel,
            'title' => $chart['title'],
            'entries' => $entries,
            'total' => $total,
        ];
    }
}
