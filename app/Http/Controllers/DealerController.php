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

    public function logout()
    {
        session()->forget('api_token');
        return redirect()->route('login');
    }
}
