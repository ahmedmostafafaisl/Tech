<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentTypeOption extends Model
{
    protected $table = 'appointment_type_options';

    protected $fillable = [
        'appointment_type_id',
        'parent_id',
        'label_ar',
        'label_en',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class, 'appointment_type_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    public function optionFields(): HasMany
    {
        return $this->hasMany(OptionField::class, 'option_id');
    }

    public function activeOptionFields(): HasMany
    {
        return $this->optionFields()->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function fields()
    {
        return $this->belongsToMany(FormField::class, 'option_fields', 'option_id', 'field_id')
            ->withPivot(['is_required', 'sort_order', 'is_active'])
            ->wherePivot('is_active', true)
            ->orderBy('option_fields.sort_order');
    }
}
