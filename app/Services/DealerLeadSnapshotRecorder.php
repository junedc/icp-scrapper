<?php

namespace App\Services;

use App\Models\DealerLeadSnapshot;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Str;
use Throwable;

class DealerLeadSnapshotRecorder
{
    /**
     * @param  array<int, array<string, mixed>>  $leads
     * @param array{
     *     dealer_id: ?int,
     *     dealer_name: ?string,
     *     user_name: ?string,
     *     user_email: ?string,
     *     source: ?string
     * } $context
     */
    public function record(array $leads, array $context, string $sourceEndpoint, ?int $queriedPage = null): void
    {
        if ($leads === []) {
            return;
        }

        $dealerScope = $this->dealerScope($context);
        $syncedAt = now();
        $rows = [];

        foreach ($leads as $lead) {
            if (! is_array($lead) || $lead === []) {
                continue;
            }

            $externalLeadId = $this->normalizeInteger($this->firstFilledValue($lead, ['id', 'lead_id']));
            $containerId = $this->normalizeInteger($this->firstFilledValue($lead, ['container_id', 'containerId']));
            $rows[] = [
                'record_key' => $this->recordKey($dealerScope, $externalLeadId, $containerId, $lead),
                'dealer_scope' => $dealerScope,
                'dealer_id' => $context['dealer_id'],
                'dealer_name' => $context['dealer_name'],
                'dealer_user_name' => $context['user_name'],
                'dealer_user_email' => $context['user_email'],
                'session_source' => $context['source'],
                'source_endpoint' => $sourceEndpoint,
                'queried_page' => $queriedPage,
                'external_lead_id' => $externalLeadId,
                'container_id' => $containerId,
                'lead_reference' => $this->normalizeString($this->firstFilledValue($lead, ['reference', 'lead_reference', 'lead_number'])),
                'status' => $this->normalizeString($this->firstFilledValue($lead, ['status'])),
                'state' => $this->normalizeString($this->firstFilledValue($lead, ['state'])),
                'currency' => $this->normalizeString($this->firstFilledValue($lead, ['currency'])),
                'amount' => $this->normalizeAmount($this->firstFilledValue($lead, ['amount', 'total', 'lead_amount'])),
                'quoted_amount' => $this->normalizeAmount($this->firstFilledValue($lead, ['quoted_amount', 'quote_amount'])),
                'order_amount' => $this->normalizeAmount($this->firstFilledValue($lead, ['order_amount'])),
                'lead_date' => $this->normalizeDate($this->firstFilledValue($lead, ['lead_date', 'date', 'created_at']), true),
                'expiry_at' => $this->normalizeDate($this->firstFilledValue($lead, ['expiry_date_time', 'expiry_at'])),
                'created_at_api' => $this->normalizeDate($this->firstFilledValue($lead, ['created_at'])),
                'updated_at_api' => $this->normalizeDate($this->firstFilledValue($lead, ['updated_at'])),
                'sent_at_api' => $this->normalizeDate($this->firstFilledValue($lead, ['sent_at', 'lead_sent_at'])),
                'raw_payload' => json_encode($lead, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
                'synced_at' => $syncedAt,
            ];
        }

        if ($rows === []) {
            return;
        }

        DealerLeadSnapshot::query()->upsert(
            $rows,
            ['record_key'],
            [
                'dealer_scope',
                'dealer_id',
                'dealer_name',
                'dealer_user_name',
                'dealer_user_email',
                'session_source',
                'source_endpoint',
                'queried_page',
                'external_lead_id',
                'container_id',
                'lead_reference',
                'status',
                'state',
                'currency',
                'amount',
                'quoted_amount',
                'order_amount',
                'lead_date',
                'expiry_at',
                'created_at_api',
                'updated_at_api',
                'sent_at_api',
                'raw_payload',
                'synced_at',
            ],
        );
    }

    /**
     * @param array{
     *     dealer_id: ?int,
     *     dealer_name: ?string,
     *     user_name: ?string,
     *     user_email: ?string,
     *     source: ?string
     * } $context
     */
    private function dealerScope(array $context): string
    {
        $base = $context['dealer_name'] ?? $context['user_email'] ?? 'unknown-dealer';
        $slug = Str::slug($base);

        if ($slug === '') {
            $slug = 'unknown-dealer';
        }

        if ($context['dealer_id'] !== null) {
            return sprintf('%s-%d', $slug, $context['dealer_id']);
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $lead
     */
    private function recordKey(string $dealerScope, ?int $externalLeadId, ?int $containerId, array $lead): string
    {
        if ($externalLeadId !== null) {
            return sprintf('%s::lead-%d', $dealerScope, $externalLeadId);
        }

        if ($containerId !== null) {
            return sprintf('%s::lead-container-%d', $dealerScope, $containerId);
        }

        return sprintf('%s::lead-hash-%s', $dealerScope, sha1(serialize($lead)));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $keys
     */
    private function firstFilledValue(array $payload, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];

            if ($value === null) {
                continue;
            }

            if (is_string($value) && trim($value) === '') {
                continue;
            }

            return $value;
        }

        return null;
    }

    private function normalizeAmount(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        if (! is_scalar($value)) {
            return null;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);

        if ($normalized === null || $normalized === '' || $normalized === '-' || ! is_numeric($normalized)) {
            return null;
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function normalizeDate(mixed $value, bool $dateOnly = false): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $dateOnly ? $value->format('Y-m-d') : $value->format('Y-m-d H:i:s');
        }

        if (! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return null;
        }

        try {
            $date = Carbon::parse($normalized);

            return $dateOnly ? $date->toDateString() : $date->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeInteger(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        if (! is_scalar($value)) {
            return null;
        }

        $normalized = preg_replace('/[^0-9\-]/', '', trim((string) $value));

        if ($normalized === null || $normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return (int) $normalized;
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null || ! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
