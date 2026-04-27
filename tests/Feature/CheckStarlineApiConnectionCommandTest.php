<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckStarlineApiConnectionCommandTest extends TestCase
{
    public function test_it_checks_the_configured_starline_api_endpoint(): void
    {
        config()->set('services.starline_api.base_url', 'https://api-master.local');
        config()->set('services.starline_api.healthcheck_path', '/');
        config()->set('services.starline_api.ca_bundle', '/opt/starline/certs/mkcert-rootCA.pem');

        Http::fake([
            'https://api-master.local/' => Http::response('<html>ok</html>', 200),
        ]);

        $this->artisan('starline-api:check')
            ->expectsOutputToContain('Connected to https://api-master.local/ [200]')
            ->assertSuccessful();

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api-master.local/';
        });
    }

    public function test_it_reports_connection_failures(): void
    {
        config()->set('services.starline_api.base_url', 'https://api-master.local');
        config()->set('services.starline_api.healthcheck_path', '/');

        Http::fake(function (): never {
            throw new ConnectionException('SSL certificate problem');
        });

        $this->artisan('starline-api:check')
            ->expectsOutputToContain('Unable to connect to https://api-master.local/: SSL certificate problem')
            ->assertFailed();
    }
}
