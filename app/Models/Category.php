<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

final class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'path',
    ];

    public function getAncestorsAttribute(): Collection
    {
        if (! $this->path) {
            return collect();
        }

        $ids = collect(explode('/', trim($this->path, '/')))
            ->filter()
            ->map(fn ($id) => (int) $id);

        return self::whereIn('id', $ids)->orderByRaw('FIELD(id, ' . $ids->implode(',') . ')')->get();
    }

    public function getDescendantsAttribute(): Collection
    {
        return self::query()
            ->where(function ($query): void {
                $query
                    ->where('path', 'LIKE', "{$this->id}/%")
                    ->orWhere('path', 'LIKE', "%/{$this->id}/%");
            })
            ->where('id', '!=', $this->id)
            ->get();
    }

    public function childrens(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
}
