<?php

namespace App\Models;

use Database\Factories\DealerOrderSyncFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealerOrderSync extends Model
{
    /** @use HasFactory<DealerOrderSyncFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'dealer_scope',
        'dealer_id',
        'dealer_name',
        'dealer_user_email',
        'session_source',
        'status',
        'current_status',
        'current_page',
        'last_page',
        'total_records',
        'delay_ms',
        'create_only',
        'error_message',
        'started_at',
        'finished_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'dealer_id' => 'integer',
        'current_page' => 'integer',
        'last_page' => 'integer',
        'total_records' => 'integer',
        'delay_ms' => 'integer',
        'create_only' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
