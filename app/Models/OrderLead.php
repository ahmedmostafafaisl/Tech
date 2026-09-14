<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderLead extends Model
{
    use HasUuids;

    protected $table = 'order_leads';

    protected $fillable = [
        'order_number',
        'customer_name',
        'mobile_number',
        'product',
        'status',
        'q1_message_id',
        'q2_message_id',
        'q1_status',
        'q2_status',
        'q1_error',
        'q2_error',
        'total_score',
        'priority',
        'rec_id',
        'dy_payload',
        'dy_response',
    ];

    protected $casts = [
        'total_score' => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    // ──────────────────────────────────────────
    // Auto-generate order_number on creation
    // Format: ORD-YYYYMMDD-XXXXX  e.g. ORD-20250623-00041
    // ──────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->order_number)) {
                $model->order_number = static::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD-' . now()->format('Ymd') . '-';

        do {
            $last   = static::where('order_number', 'like', $prefix . '%')
                ->lockForUpdate()
                ->max('order_number');

            $next   = $last ? ((int) substr($last, -5)) + 1 : 1;
            $number = $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function responses(): HasMany
    {
        return $this->hasMany(LeadResponse::class, 'lead_id')
            ->orderBy('question_no');
    }

    // ──────────────────────────────────────────
    // Status helpers
    // ──────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
    public function isQ1Sent(): bool
    {
        return $this->status === 'q1_sent';
    }
    public function isQ1Answered(): bool
    {
        return $this->status === 'q1_answered';
    }
    public function isQ2Sent(): bool
    {
        return $this->status === 'q2_sent';
    }
    public function isQ2Answered(): bool
    {
        return $this->status === 'q2_answered';
    }
    public function isScored(): bool
    {
        return $this->status === 'scored';
    }
    public function isInquiry(): bool
    {
        return $this->status === 'inquiry';
    }
    public function isQ1Failed(): bool
    {
        return $this->q1_status === 'failed';
    }
    public function isQ2Failed(): bool
    {
        return $this->q2_status === 'failed';
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByScore($query)
    {
        return $query->orderByDesc('total_score')->orderByDesc('created_at');
    }
}
