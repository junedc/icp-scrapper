<?php

namespace App\Console\Commands;

use App\Services\StarlineApiClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;

class CheckStarlineApiConnection extends Command
{
    protected $signature = 'starline-api:check {path? : Relative path to request}';

    protected $description = 'Verify connectivity from icp_scrapper to the configured Starline API endpoint';

    public function handle(StarlineApiClient $starlineApiClient): int
    {
        $path = $this->argument('path');
        $requestPath = is_string($path) && $path !== ''
            ? $path
            : $starlineApiClient->healthcheckPath();
        $requestPath = str_starts_with($requestPath, '/') ? $requestPath : '/'.$requestPath;

        try {
            $response = $starlineApiClient->get($requestPath);
        } catch (ConnectionException $exception) {
            $this->components->error(sprintf(
                'Unable to connect to %s%s: %s',
                $starlineApiClient->baseUrl(),
                $requestPath,
                $exception->getMessage()
            ));

            return self::FAILURE;
        }

        $url = $starlineApiClient->baseUrl().$requestPath;
        $status = $response->status();

        if ($response->successful()) {
            $this->components->info(sprintf('Connected to %s [%d]', $url, $status));

            return self::SUCCESS;
        }

        $this->components->error(sprintf('Received [%d] from %s', $status, $url));

        return self::FAILURE;
    }
}
