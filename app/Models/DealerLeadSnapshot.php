<?php

namespace App\Models;

use Database\Factories\DealerLeadSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealerLeadSnapshot extends Model
{
    /** @use HasFactory<DealerLeadSnapshotFactory> */
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
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'dealer_id' => 'integer',
        'queried_page' => 'integer',
        'external_lead_id' => 'integer',
        'container_id' => 'integer',
        'amount' => 'decimal:2',
        'quoted_amount' => 'decimal:2',
        'order_amount' => 'decimal:2',
        'lead_date' => 'date',
        'expiry_at' => 'datetime',
        'created_at_api' => 'datetime',
        'updated_at_api' => 'datetime',
        'sent_at_api' => 'datetime',
        'raw_payload' => 'array',
        'synced_at' => 'datetime',
    ];
}
