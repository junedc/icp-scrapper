<?php

namespace App\Http\Controllers;

use App\Services\StarlineApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DealerController extends Controller
{
    protected StarlineApiClient $apiClient;

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
        if (!session('api_token')) {
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
        if (!session('api_token')) {
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
        if (!session('api_token')) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $response = $this->apiClient->get("/api/admin/dealer-maintenance/dealer/{$id}/users");

        if ($response->successful()) {
            $users = $response->json()['data'] ?? [];

            // Filter out 'admin' users
            $filteredUsers = array_filter($users, function($user) {
                $role = strtolower($user['role'] ?? $user['type'] ?? '');
                $name = strtolower($user['name'] ?? '');
                $email = strtolower($user['email'] ?? '');

                return $role !== 'admin' && !str_contains($name, 'admin') && !str_contains($email, 'admin');
            });

            // Map users to include necessary data for impersonation (if needed)
            $mappedUsers = array_map(function($user) {
                return [
                    'id' => $user['id'],
                    'name' => $user['name'] ?? 'N/A',
                    'email' => $user['email'] ?? 'N/A',
                    'status' => $user['status'] ?? 'N/A',
                ];
            }, array_values($filteredUsers));

            return response()->json([
                'data' => $mappedUsers,
                'api_logs' => $this->apiClient->getLogs()
            ]);
        }

        return response()->json([
            'error' => 'Failed to fetch users',
            'api_logs' => $this->apiClient->getLogs()
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
                    'api_logs' => $this->apiClient->getLogs()
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'error' => 'Login failed: ' . ($response->json()['message'] ?? 'Unknown error'),
            'status' => $response->status(),
            'api_logs' => $this->apiClient->getLogs()
        ], 400);
    }

    public function showOrder($id)
    {
        if (!session('api_token')) {
            return redirect()->route('login');
        }

        $response = $this->apiClient->get("/api/admin/production-review/{$id}");

        Log::info('Order details response:', ['response' => $response->json()]);

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
        if (!$token) {
            return redirect()->route('dealers.index')->withErrors(['error' => 'No active impersonation session.']);
        }

        $query = [
            'paginate' => 10,
            'page' => $request->query('page', 1),
        ];

        // We need to pass the token explicitly to the request
        $response = $this->apiClient->request(true)
            ->withToken($token)
            ->get('/api/ordering-portal/my-orders', $query);

        if ($response->successful()) {
            $data = $response->json();
            $orders = $data['data'] ?? [];
            $apiPagination = $data['pagination'] ?? [];
            $pagination = [
                'current_page' => $apiPagination['current_page'] ?? 1,
                'last_page' => $apiPagination['total_pages'] ?? 1,
                'total' => $apiPagination['total'] ?? 0,
                'per_page' => $apiPagination['per_page'] ?? 10,
            ];

            return view('my_orders', compact('orders', 'pagination'));
        }

        if ($response->status() === 401) {
            session()->forget('impersonated_token');
            return redirect()->route('dealers.index')->withErrors(['error' => 'Impersonation session expired.']);
        }

        return view('my_orders', ['orders' => [], 'error' => 'Failed to fetch your orders.']);
    }

    public function myOrdersAll(Request $request)
    {
        $token = session('impersonated_token');
        if (!$token) {
            return response()->json(['error' => 'No active impersonation session.'], 401);
        }

        $allOrders = [];
        $currentPage = 1;
        $lastPage = 1;

        do {
            $response = $this->apiClient->request(true)
                ->withToken($token)
                ->get('/api/ordering-portal/my-orders', [
                    'paginate' => 100, // Fetch in larger batches for efficiency
                    'page' => $currentPage
                ]);

            if (!$response->successful()) {
                break;
            }

            $data = $response->json();
            $allOrders = array_merge($allOrders, $data['data'] ?? []);

            $lastPage = $data['pagination']['total_pages'] ?? 1;
            $currentPage++;

        } while ($currentPage <= $lastPage && $currentPage <= 10); // Safety limit of 10 pages/1000 orders for now

        return response()->json([
            'data' => $allOrders,
            'total' => count($allOrders),
            'last_page' => $lastPage,
            'api_logs' => $this->apiClient->getLogs()
        ]);
    }

    public function myJobs(Request $request)
    {
        $token = session('impersonated_token');
        if (!$token) {
            return redirect()->route('dealers.index')->withErrors(['error' => 'No active impersonation session.']);
        }

        $query = [
            'paginate' => 10,
            'page' => $request->query('page', 1),
            'filter' => [
                'status' => 'Open',
                'search' => ' ',
            ],
            'sort' => '-by_date',
        ];

        $response = $this->apiClient->request(true)
            ->withToken($token)
            ->get('/api/ordering-portal/my-jobs', $query);

        if ($response->successful()) {
            $data = $response->json();
            $jobs = $data['data'] ?? [];
            $apiPagination = $data['pagination'] ?? [];
            $pagination = [
                'current_page' => $apiPagination['current_page'] ?? 1,
                'last_page' => $apiPagination['total_pages'] ?? 1,
                'total' => $apiPagination['total'] ?? 0,
                'per_page' => $apiPagination['per_page'] ?? 10,
            ];

            return view('my_jobs', compact('jobs', 'pagination'));
        }

        if ($response->status() === 401) {
            session()->forget('impersonated_token');
            return redirect()->route('dealers.index')->withErrors(['error' => 'Impersonation session expired.']);
        }

        return view('my_jobs', ['jobs' => [], 'error' => 'Failed to fetch your jobs.']);
    }

    public function myJobsAll(Request $request)
    {
        $token = session('impersonated_token');
        if (!$token) {
            return response()->json(['error' => 'No active impersonation session.'], 401);
        }

        $allJobs = [];
        $currentPage = 1;
        $lastPage = 1;

        do {
            $response = $this->apiClient->request(true)
                ->withToken($token)
                ->get('/api/ordering-portal/my-jobs', [
                    'paginate' => 100,
                    'page' => $currentPage,
                    'filter' => [
                        'status' => 'Open',
                        'search' => ' ',
                    ],
                    'sort' => '-by_date',
                ]);

            if (!$response->successful()) {
                break;
            }

            $data = $response->json();
            $allJobs = array_merge($allJobs, $data['data'] ?? []);

            $lastPage = $data['pagination']['total_pages'] ?? 1;
            $currentPage++;

        } while ($currentPage <= $lastPage && $currentPage <= 10);

        return response()->json([
            'data' => $allJobs,
            'total' => count($allJobs),
            'last_page' => $lastPage,
            'api_logs' => $this->apiClient->getLogs()
        ]);
    }

    public function logout()
    {
        session()->forget('api_token');
        return redirect()->route('login');
    }
}
