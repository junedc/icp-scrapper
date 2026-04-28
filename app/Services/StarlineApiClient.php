<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class StarlineApiClient
{
    public function request(bool $useOrderingHeaders = false): PendingRequest
    {
        $origin = $useOrderingHeaders
            ? config('services.starline_api.ordering_origin')
            : config('services.starline_api.origin');

        $referer = $useOrderingHeaders
            ? config('services.starline_api.ordering_referer')
            : config('services.starline_api.referer');

        $request = Http::acceptJson()
            ->baseUrl($this->baseUrl())
            ->withHeaders([
                'Origin' => $origin,
                'Referer' => $referer,
            ])
            ->connectTimeout($this->connectTimeout())
            ->timeout($this->timeout())
            ->withOptions([
                'verify' => $this->verifyOption(),
            ]);

        if ($token = Session::get('api_token')) {
            $request->withToken($token);
        }

        return $request;
    }

    public function get(string $path, array $query = [], bool $useOrderingHeaders = false): Response
    {
        return $this->request($useOrderingHeaders)->get($this->normalizePath($path), $query);
    }

    public function post(string $path, array $payload = [], bool $useOrderingHeaders = false): Response
    {
        return $this->request($useOrderingHeaders)->post($this->normalizePath($path), $payload);
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.starline_api.base_url', ''), '/');
    }

    public function healthcheckPath(): string
    {
        return $this->normalizePath((string) config('services.starline_api.healthcheck_path', '/'));
    }

    private function connectTimeout(): int
    {
        return (int) config('services.starline_api.connect_timeout', 10);
    }

    private function timeout(): int
    {
        return (int) config('services.starline_api.timeout', 60);
    }

    private function verifyOption(): bool|string
    {
        $caBundle = config('services.starline_api.ca_bundle');

        if (is_string($caBundle) && $caBundle !== '' && file_exists($caBundle)) {
            return $caBundle;
        }

        $verify = config('services.starline_api.verify', true);

        if (is_bool($verify)) {
            return $verify;
        }

        $normalized = filter_var($verify, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($normalized !== null) {
            return $normalized;
        }

        return (string) $verify;
    }

    private function normalizePath(string $path): string
    {
        $trimmedPath = trim($path);

        if ($trimmedPath === '') {
            return '/';
        }

        return str_starts_with($trimmedPath, '/') ? $trimmedPath : '/'.$trimmedPath;
    }
}
