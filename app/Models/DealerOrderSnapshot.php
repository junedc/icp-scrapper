<?php

namespace App\Models;

use Database\Factories\DealerOrderSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealerOrderSnapshot extends Model
{
    /** @use HasFactory<DealerOrderSnapshotFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'record_key',
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
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'dealer_id' => 'integer',
        'queried_page' => 'integer',
        'external_order_id' => 'integer',
        'container_id' => 'integer',
        'lead_id' => 'integer',
        'total_amount' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'order_date' => 'date',
        'submitted_at_api' => 'datetime',
        'created_at_api' => 'datetime',
        'updated_at_api' => 'datetime',
        'paid_at_api' => 'datetime',
        'lead_sent_at' => 'datetime',
        'raw_payload' => 'array',
        'synced_at' => 'datetime',
    ];
}
