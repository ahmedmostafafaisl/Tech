<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadResponse extends Model
{
    use HasUuids;

    protected $table = 'lead_responses';

    public $timestamps = true;

    protected $fillable = [
        'lead_id',
        'ref_number',
        'question_no',
        'answer_text',
        'score',
        'answered_at',
    ];

    protected $casts = [
        'question_no' => 'integer',
        'score'       => 'integer',
        'answered_at' => 'datetime',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function lead(): BelongsTo
    {
        return $this->belongsTo(OrderLead::class, 'lead_id');
    }
}
