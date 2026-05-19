<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageDeliveryLog extends Model
{
    protected $table = 'message_delivery_log';

    protected $fillable = [
        'whatsapp_message_id',
        'phone',
        'template_name',
        'category',
        'direction',
        'status',
        'cost_usd',
        'content_preview',
        'user_id',
        'flow_type',
        'sent_at',
        'delivered_at',
        'read_at',
        'error_message',
    ];

    protected $casts = [
        'cost_usd'     => 'decimal:6',
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'read_at'      => 'datetime',
    ];

    /**
     * The KlassApp user this message was sent to (if identifiable).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope: filter by status.
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: filter by date range.
     */
    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('sent_at', [$from, $to]);
    }

    /**
     * Scope: filter by flow type.
     */
    public function scopeOfFlowType($query, string $type)
    {
        return $query->where('flow_type', $type);
    }

    /**
     * Mark this message as delivered.
     */
    public function markDelivered(): void
    {
        $this->update([
            'status'         => 'delivered',
            'delivered_at'   => now(),
        ]);
    }

    /**
     * Mark this message as read.
     */
    public function markRead(): void
    {
        $this->update([
            'status'  => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * Mark this message as failed.
     */
    public function markFailed(string $error = ''): void
    {
        $this->update([
            'status'        => 'failed',
            'error_message' => $error,
        ]);
    }
}
