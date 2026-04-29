<?php

namespace App\Services;

use App\Models\DealerOrderSnapshot;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Str;
use Throwable;

class DealerOrderSnapshotRecorder
{
    /**
     * @param  array<int, array<string, mixed>>  $orders
     * @param array{
     *     dealer_id: ?int,
     *     dealer_name: ?string,
     *     user_name: ?string,
     *     user_email: ?string,
     *     source: ?string
     * } $context
     */
    public function record(array $orders, array $context, string $sourceEndpoint, ?string $queriedStatus = null, ?int $queriedPage = null): void
    {
        if ($orders === []) {
            return;
        }

        $dealerScope = $this->dealerScope($context);
        $syncedAt = now();
        $rows = [];

        foreach ($orders as $order) {
            if (! is_array($order) || $order === []) {
                continue;
            }

            $externalOrderId = $this->normalizeInteger($this->firstFilledValue($order, ['id', 'order_id']));
            $containerId = $this->normalizeInteger($this->firstFilledValue($order, ['container_id', 'containerId']));
            $rows[] = [
                'record_key' => $this->recordKey($dealerScope, $externalOrderId, $containerId, $order),
                'dealer_scope' => $dealerScope,
                'dealer_id' => $context['dealer_id'],
                'dealer_name' => $context['dealer_name'],
                'dealer_user_name' => $context['user_name'],
                'dealer_user_email' => $context['user_email'],
                'session_source' => $context['source'],
                'source_endpoint' => $sourceEndpoint,
                'queried_status' => $queriedStatus,
                'queried_page' => $queriedPage,
                'external_order_id' => $externalOrderId,
                'container_id' => $containerId,
                'order_number' => $this->normalizeString($this->firstFilledValue($order, ['order_number', 'order_no', 'number', 'reference'])),
                'dealer_reference' => $this->normalizeString($this->firstFilledValue($order, ['dealer_reference', 'dealerReference'])),
                'customer_name' => $this->normalizeString($this->firstFilledValue($order, ['customer_name', 'customer', 'client_name', 'contact_name'])),
                'status' => $this->normalizeString($this->firstFilledValue($order, ['status'])),
                'state' => $this->normalizeString($this->firstFilledValue($order, ['state'])),
                'payment_status' => $this->normalizeString($this->firstFilledValue($order, ['payment_status', 'payment_state'])),
                'payment_method' => $this->normalizeString($this->firstFilledValue($order, ['payment_method', 'payment_type'])),
                'currency' => $this->normalizeString($this->firstFilledValue($order, ['currency'])),
                'total_amount' => $this->normalizeAmount($this->firstFilledValue($order, ['total', 'total_amount', 'grand_total'])),
                'subtotal_amount' => $this->normalizeAmount($this->firstFilledValue($order, ['subtotal', 'sub_total', 'subtotal_amount'])),
                'discount_amount' => $this->normalizeAmount($this->firstFilledValue($order, ['discount', 'discount_amount'])),
                'deposit_amount' => $this->normalizeAmount($this->firstFilledValue($order, ['deposit', 'deposit_amount'])),
                'paid_amount' => $this->normalizeAmount($this->firstFilledValue($order, ['paid_amount', 'amount_paid', 'payments_total'])),
                'balance_amount' => $this->normalizeAmount($this->firstFilledValue($order, ['balance', 'balance_due', 'amount_due'])),
                'shipping_amount' => $this->normalizeAmount($this->firstFilledValue($order, ['shipping', 'shipping_amount', 'delivery_amount'])),
                'tax_amount' => $this->normalizeAmount($this->firstFilledValue($order, ['tax', 'tax_amount', 'gst', 'gst_amount'])),
                'lead_id' => $this->normalizeInteger($this->firstFilledValue($order, ['lead_id', 'leadId'])),
                'lead_reference' => $this->normalizeString($this->firstFilledValue($order, ['lead_reference', 'lead_number', 'lead_code'])),
                'order_date' => $this->normalizeDate($this->firstFilledValue($order, ['order_date', 'date']), true),
                'submitted_at_api' => $this->normalizeDate($this->firstFilledValue($order, ['submitted_at', 'submitted_date'])),
                'created_at_api' => $this->normalizeDate($this->firstFilledValue($order, ['created_at'])),
                'updated_at_api' => $this->normalizeDate($this->firstFilledValue($order, ['updated_at'])),
                'paid_at_api' => $this->normalizeDate($this->firstFilledValue($order, ['paid_at', 'payment_date'])),
                'lead_sent_at' => $this->normalizeDate($this->firstFilledValue($order, ['lead_sent_at', 'lead_sent_date'])),
                'raw_payload' => json_encode($order, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
                'synced_at' => $syncedAt,
            ];
        }

        if ($rows === []) {
            return;
        }

        DealerOrderSnapshot::query()->upsert(
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
                'queried_status',
                'queried_page',
                'external_order_id',
                'container_id',
                'order_number',
                'dealer_reference',
                'customer_name',
                'status',
                'state',
                'payment_status',
                'payment_method',
                'currency',
                'total_amount',
                'subtotal_amount',
                'discount_amount',
                'deposit_amount',
                'paid_amount',
                'balance_amount',
                'shipping_amount',
                'tax_amount',
                'lead_id',
                'lead_reference',
                'order_date',
                'submitted_at_api',
                'created_at_api',
                'updated_at_api',
                'paid_at_api',
                'lead_sent_at',
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
     * @param  array<string, mixed>  $order
     */
    private function recordKey(string $dealerScope, ?int $externalOrderId, ?int $containerId, array $order): string
    {
        if ($externalOrderId !== null) {
            return sprintf('%s::order-%d', $dealerScope, $externalOrderId);
        }

        if ($containerId !== null) {
            return sprintf('%s::container-%d', $dealerScope, $containerId);
        }

        return sprintf('%s::hash-%s', $dealerScope, sha1(serialize($order)));
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
