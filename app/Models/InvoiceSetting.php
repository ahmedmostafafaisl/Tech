<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    public function getParsedValueAttribute(): mixed
    {
        if ($this->type === 'json') {
            return json_decode($this->value, true) ?? [];
        }

        return $this->value;
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return $setting->parsed_value;
    }

    public static function setValue(string $key, mixed $value, string $type = 'text'): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $type === 'json'
                    ? json_encode($value, JSON_UNESCAPED_UNICODE)
                    : $value,
                'type' => $type,
            ]
        );
    }

    public static function allAsArray(): array
    {
        return static::query()
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->key => $item->parsed_value];
            })
            ->toArray();
    }
}
