<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class NearPayTerminal extends Model
{
    // Eloquent's default inference splits "NearPay" into "near_pay", giving "near_pay_terminals" — the
    // actual migration (matching the existing nearpay_webhooks naming already used elsewhere) created
    // nearpay_terminals as one word, so this must be explicit.
    protected $table = 'nearpay_terminals';

    protected $fillable = [
        'terminal_id',
        'trsm_code',
        'tid',
        'name',
        'is_connected',
        'remote_user_name',
        'remote_user_email',
        'remote_user_mobile',
        'last_synced_at',
        'assigned_user_id',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at'    => 'datetime',
        'last_synced_at' => 'datetime',
        'is_connected'   => 'boolean',
    ];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function isAssigned(): bool
    {
        return $this->assigned_user_id !== null;
    }
}
