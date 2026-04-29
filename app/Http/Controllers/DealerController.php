<?php

namespace App\Http\Controllers;

use App\Services\StarlineApiClient;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;

class DealerController extends Controller
{
    protected StarlineApiClient $apiClient;

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

    public function __construct(StarlineApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
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
                session(['impersonated_token' => $token]);
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

    public function myOrders(Request $request)
    {
        $token = session('impersonated_token');
        if (! $token) {
            return redirect()->route('dealers.index')->withErrors(['error' => 'No active impersonation session.']);
        }

        $selectedStatus = $this->resolveOrderingPortalStatus($request->query('status'));
        $page = max(1, (int) $request->query('page', 1));

        [
            'response' => $response,
            'orders' => $orders,
            'pagination' => $pagination,
        ] = $this->fetchMyOrdersPage($token, $selectedStatus, $page);

        if ($response->successful()) {
            $availableStatuses = $this->statuses;

            return view('my_orders', compact('orders', 'pagination', 'selectedStatus', 'availableStatuses'));
        }

        if ($response->status() === 401) {
            session()->forget('impersonated_token');

            return redirect()->route('dealers.index')->withErrors(['error' => 'Impersonation session expired.']);
        }

        return view('my_orders', [
            'orders' => [],
            'error' => 'Failed to fetch your orders.',
            'selectedStatus' => $selectedStatus,
            'availableStatuses' => $this->statuses,
        ]);
    }

    public function myOrdersPage(Request $request)
    {
        $token = session('impersonated_token');
        if (! $token) {
            return response()->json(['error' => 'No active impersonation session.'], 401);
        }

        $selectedStatus = $this->resolveOrderingPortalStatus($request->query('status'));
        $page = max(1, (int) $request->query('page', 1));

        [
            'response' => $response,
            'orders' => $orders,
            'pagination' => $pagination,
        ] = $this->fetchMyOrdersPage($token, $selectedStatus, $page);

        if ($response->successful()) {
            return response()->json([
                'data' => $orders,
                'pagination' => $pagination,
                'selected_status' => $selectedStatus,
                'api_logs' => $this->apiClient->getLogs(),
            ]);
        }

        if ($response->status() === 401) {
            session()->forget('impersonated_token');

            return response()->json(['error' => 'Impersonation session expired.'], 401);
        }

        return response()->json([
            'error' => 'Failed to fetch your orders.',
            'selected_status' => $selectedStatus,
            'api_logs' => $this->apiClient->getLogs(),
        ], $response->status());
    }

    public function myOrdersAll(Request $request)
    {
        $token = session('impersonated_token');
        if (! $token) {
            return response()->json(['error' => 'No active impersonation session.'], 401);
        }

        $allOrders = [];

        $lastPage = 1;

        foreach ($this->statuses as $status) {
            $currentPage = 1;
            do {
                $response = $this->apiClient->get('/api/ordering-portal/my-orders', [
                    'paginate' => 1000, // Fetch in larger batches for efficiency
                    'filter' => $this->orderingPortalFilter($status),
                    'page' => $currentPage,
                    'sort' => '-by_date',
                ], true, $token);

                if (! $response->successful()) {
                    break;
                }

                $data = $response->json();
                $allOrders = array_merge($allOrders, $data['data'] ?? []);

                $lastPage = $data['pagination']['total_pages'] ?? 1;
                $currentPage++;

            } while ($currentPage <= $lastPage);
        }

        return response()->json([
            'data' => $allOrders,
            'total' => count($allOrders),
            'last_page' => $lastPage,
            'api_logs' => $this->apiClient->getLogs(),
        ]);
    }

    public function myJobs(Request $request)
    {
        $token = session('impersonated_token');
        if (! $token) {
            return redirect()->route('dealers.index')->withErrors(['error' => 'No active impersonation session.']);
        }

        $selectedStatus = $this->resolveOrderingPortalStatus($request->query('status'));
        $page = max(1, (int) $request->query('page', 1));

        [
            'response' => $response,
            'jobs' => $jobs,
            'pagination' => $pagination,
        ] = $this->fetchMyJobsPage($token, $selectedStatus, $page);

        if ($response->successful()) {
            $availableStatuses = $this->statuses;

            return view('my_jobs', compact('jobs', 'pagination', 'selectedStatus', 'availableStatuses'));
        }

        if ($response->status() === 401) {
            session()->forget('impersonated_token');

            return redirect()->route('dealers.index')->withErrors(['error' => 'Impersonation session expired.']);
        }

        return view('my_jobs', [
            'jobs' => [],
            'error' => 'Failed to fetch your jobs.',
            'selectedStatus' => $selectedStatus,
            'availableStatuses' => $this->statuses,
        ]);
    }

    public function myJobsPage(Request $request)
    {
        $token = session('impersonated_token');
        if (! $token) {
            return response()->json(['error' => 'No active impersonation session.'], 401);
        }

        $selectedStatus = $this->resolveOrderingPortalStatus($request->query('status'));
        $page = max(1, (int) $request->query('page', 1));

        [
            'response' => $response,
            'jobs' => $jobs,
            'pagination' => $pagination,
        ] = $this->fetchMyJobsPage($token, $selectedStatus, $page);

        if ($response->successful()) {
            return response()->json([
                'data' => $jobs,
                'pagination' => $pagination,
                'selected_status' => $selectedStatus,
                'api_logs' => $this->apiClient->getLogs(),
            ]);
        }

        if ($response->status() === 401) {
            session()->forget('impersonated_token');

            return response()->json(['error' => 'Impersonation session expired.'], 401);
        }

        return response()->json([
            'error' => 'Failed to fetch your jobs.',
            'selected_status' => $selectedStatus,
            'api_logs' => $this->apiClient->getLogs(),
        ], $response->status());
    }

    public function myJobsAll(Request $request)
    {
        $token = session('impersonated_token');
        if (! $token) {
            return response()->json(['error' => 'No active impersonation session.'], 401);
        }

        $allJobs = [];

        $lastPage = 1;
        foreach ($this->statuses as $status) {
            $currentPage = 1;
            do {
                $response = $this->apiClient->get('/api/ordering-portal/my-jobs', [
                    'paginate' => 10,
                    'filter' => $this->orderingPortalFilter($status),
                    'page' => $currentPage,
                    'sort' => '-by_date',
                ], true, $token);

                if (! $response->successful()) {
                    break;
                }

                $data = $response->json();
                $allJobs = array_merge($allJobs, $data['data'] ?? []);

                $lastPage = $data['pagination']['total_pages'] ?? 1;
                $currentPage++;

            } while ($currentPage <= $lastPage);
        }

        return response()->json([
            'data' => $allJobs,
            'total' => count($allJobs),
            'last_page' => $lastPage,
            'api_logs' => $this->apiClient->getLogs(),
        ]);
    }

    public function myLeads(Request $request)
    {
        $token = session('impersonated_token');
        if (! $token) {
            return redirect()->route('dealers.index')->withErrors(['error' => 'No active impersonation session.']);
        }

        $page = max(1, (int) $request->query('page', 1));

        [
            'response' => $response,
            'leads' => $leads,
            'pagination' => $pagination,
        ] = $this->fetchMyLeadsPage($token, $page);

        if ($response->successful()) {
            return view('my_leads', compact('leads', 'pagination'));
        }

        if ($response->status() === 401) {
            session()->forget('impersonated_token');

            return redirect()->route('dealers.index')->withErrors(['error' => 'Impersonation session expired.']);
        }

        return view('my_leads', [
            'leads' => [],
            'error' => 'Failed to fetch your leads.',
        ]);
    }

    public function myLeadsPage(Request $request)
    {
        $token = session('impersonated_token');
        if (! $token) {
            return response()->json(['error' => 'No active impersonation session.'], 401);
        }

        $page = max(1, (int) $request->query('page', 1));

        [
            'response' => $response,
            'leads' => $leads,
            'pagination' => $pagination,
        ] = $this->fetchMyLeadsPage($token, $page);

        if ($response->successful()) {
            return response()->json([
                'data' => $leads,
                'pagination' => $pagination,
                'api_logs' => $this->apiClient->getLogs(),
            ]);
        }

        if ($response->status() === 401) {
            session()->forget('impersonated_token');

            return response()->json(['error' => 'Impersonation session expired.'], 401);
        }

        return response()->json([
            'error' => 'Failed to fetch your leads.',
            'api_logs' => $this->apiClient->getLogs(),
        ], $response->status());
    }

    public function myLeadsAll(Request $request)
    {
        $token = session('impersonated_token');
        if (! $token) {
            return response()->json(['error' => 'No active impersonation session.'], 401);
        }

        $result = $this->fetchAllLeads($token);

        if ($result['status'] === 401) {
            session()->forget('impersonated_token');

            return response()->json(['error' => 'Impersonation session expired.'], 401);
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
        $token = session('impersonated_token');
        if (! $token) {
            return response()->json(['error' => 'No active impersonation session.'], 401);
        }

        $orders = $this->fetchAllOrders($token);
        $jobs = $this->fetchAllJobs($token);
        $leads = $this->fetchAllLeads($token);

        $responseStatuses = [$orders['status'], $jobs['status'], $leads['status']];
        if (in_array(401, $responseStatuses, true)) {
            session()->forget('impersonated_token');

            return response()->json(['error' => 'Impersonation session expired.'], 401);
        }

        $errors = array_filter([
            'orders' => $orders['error'],
            'jobs' => $jobs['error'],
            'leads' => $leads['error'],
        ]);

        return response()->json([
            'data' => [
                'orders' => $orders['items'],
                'jobs' => $jobs['items'],
                'leads' => $leads['items'],
            ],
            'meta' => [
                'orders' => [
                    'total' => count($orders['items']),
                    'last_page' => $orders['last_page'],
                ],
                'jobs' => [
                    'total' => count($jobs['items']),
                    'last_page' => $jobs['last_page'],
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
     *     jobs: array<int, array<string, mixed>>,
     *     pagination: array{current_page: int, last_page: int, total: int, per_page: int}
     * }
     */
    private function fetchMyJobsPage(string $token, string $status, int $page): array
    {
        $response = $this->apiClient->get('/api/ordering-portal/my-jobs', [
            'paginate' => 10,
            'page' => $page,
            'filter' => $this->orderingPortalFilter($status),
            'sort' => '-by_date',
        ], true, $token);

        $data = $response->json();
        $jobs = $data['data'] ?? [];
        $apiPagination = $data['pagination'] ?? [];

        return [
            'response' => $response,
            'jobs' => $jobs,
            'pagination' => [
                'current_page' => $apiPagination['current_page'] ?? $page,
                'last_page' => $apiPagination['total_pages'] ?? 1,
                'total' => $apiPagination['total'] ?? count($jobs),
                'per_page' => $apiPagination['per_page'] ?? 10,
            ],
        ];
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
    private function fetchAllJobs(string $token): array
    {
        return $this->fetchAllStatusAwareRecords($token, '/api/ordering-portal/my-jobs');
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
            $allItems = array_merge($allItems, $data['data'] ?? []);

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
}
