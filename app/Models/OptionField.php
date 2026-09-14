<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptionField extends Model
{
    protected $table = 'option_fields';

    protected $fillable = [
        'option_id',
        'field_id',
        'is_required',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'sort_order'  => 'integer',
        'is_active'   => 'boolean',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(AppointmentTypeOption::class, 'option_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'field_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
