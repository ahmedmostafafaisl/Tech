<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class DyEnvironment extends Model
{
    protected $fillable = ['name', 'url', 'is_default'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * The currently-active base URL, cached briefly so DyService's
     * constructor (which runs on every request that resolves it) doesn't
     * hit the DB every time. Falls back to the production URL if the
     * table is empty or unreachable (e.g. before this migration has run),
     * so the app never silently ends up with no base URL at all.
     */
    public static function getDefaultUrl(): string
    {
        try {
            return Cache::remember('dy_environment:default_url', now()->addMinutes(5), function () {
                $default = static::where('is_default', true)->first();

                return $default?->url ?? 'https://hamat-prod.operations.eu.dynamics.com';
            });
        } catch (\Throwable $e) {
            return 'https://hamat-prod.operations.eu.dynamics.com';
        }
    }

    /**
     * Switches the default environment to the given one, unsetting any
     * other current default. Clears the cache so DyService picks up the
     * change on its very next construction, not after 5 minutes.
     */
    public static function switchTo(int $id): self
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $target = static::findOrFail($id);

            static::where('is_default', true)->update(['is_default' => false]);
            $target->update(['is_default' => true]);

            Cache::forget('dy_environment:default_url');

            return $target;
        });
    }
}
