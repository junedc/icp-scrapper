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

    public function index()
    {
        if (!session('api_token')) {
            return redirect()->route('login');
        }

        $response = $this->apiClient->get('/api/admin/customers/dealers');

        if ($response->successful()) {
            $dealers = $response->json()['data'] ?? $response->json();
            return view('dealers', compact('dealers'));
        }

        if ($response->status() === 401) {
            session()->forget('api_token');
            return redirect()->route('login')->withErrors(['login' => 'Session expired. Please login again.']);
        }

        return view('dealers', ['dealers' => [], 'error' => 'Failed to fetch dealers.']);
    }

    public function logout()
    {
        session()->forget('api_token');
        return redirect()->route('login');
    }
}
