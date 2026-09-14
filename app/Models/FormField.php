<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    protected $table = 'form_fields';

    protected $fillable = [
        'field_key',
        'label_ar',
        'label_en',
        'field_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function optionFields(): HasMany
    {
        return $this->hasMany(OptionField::class, 'field_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
