<?php

namespace App\Jobs;

use App\Models\DealerOrderSync;
use App\Services\DealerOrderSnapshotRecorder;
use App\Services\StarlineApiClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class FetchDealerOrdersSnapshotJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    /**
     * @param  array<int, string>  $statuses
     * @param array{
     *     dealer_id: ?int,
     *     dealer_name: ?string,
     *     user_name: ?string,
     *     user_email: ?string,
     *     source: ?string
     * } $context
     */
    public function __construct(
        public int $syncId,
        public string $token,
        public array $statuses,
        public array $context,
        public int $delayMs = 350,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(StarlineApiClient $apiClient, DealerOrderSnapshotRecorder $snapshotRecorder): void
    {
        $sync = DealerOrderSync::query()->find($this->syncId);

        if (! $sync) {
            return;
        }

        $sync->forceFill([
            'status' => 'running',
            'started_at' => now(),
            'error_message' => null,
        ])->save();

        $totalRecords = 0;

        foreach ($this->statuses as $status) {
            $currentPage = 1;
            $lastPage = 1;

            do {
                $sync->forceFill([
                    'current_status' => $status,
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                ])->save();

                $response = $apiClient->get('/api/ordering-portal/my-orders', [
                    'paginate' => 1000,
                    'filter' => ['status' => $status, 'search' => ' '],
                    'page' => $currentPage,
                    'sort' => '-by_date',
                ], true, $this->token);

                if ($response->status() === 401) {
                    throw new \RuntimeException('Dealer session expired while processing queued order sync.');
                }

                if (! $response->successful()) {
                    throw new \RuntimeException(sprintf('Failed to fetch "%s" orders on page %d.', $status, $currentPage));
                }

                $payload = $response->json();
                $orders = $payload['data'] ?? [];

                $snapshotRecorder->record(
                    orders: $orders,
                    context: $this->context,
                    sourceEndpoint: '/api/ordering-portal/my-orders',
                    queriedStatus: $status,
                    queriedPage: $currentPage,
                    createOnly: true,
                );

                $totalRecords += count($orders);
                $lastPage = (int) ($payload['pagination']['total_pages'] ?? 1);

                $sync->forceFill([
                    'current_status' => $status,
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'total_records' => $totalRecords,
                ])->save();

                $currentPage++;

                if ($currentPage <= $lastPage && $this->delayMs > 0) {
                    usleep($this->delayMs * 1000);
                }
            } while ($currentPage <= $lastPage);

            if ($this->delayMs > 0) {
                usleep($this->delayMs * 1000);
            }
        }

        $sync->forceFill([
            'status' => 'completed',
            'total_records' => $totalRecords,
            'finished_at' => now(),
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        $sync = DealerOrderSync::query()->find($this->syncId);

        if (! $sync) {
            return;
        }

        $sync->forceFill([
            'status' => 'failed',
            'error_message' => $exception?->getMessage(),
            'finished_at' => now(),
        ])->save();
    }
}
