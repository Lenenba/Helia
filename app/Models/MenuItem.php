<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MenuItem extends Model
{
    /** @use HasFactory<\Database\Factories\MenuItemFactory> */
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'position',
        'label',
        'url',
        'is_visible',
        'meta',
        'linkable_id',
        'linkable_type',
    ];

    protected $casts = ['meta' => 'array', 'is_visible' => 'boolean'];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('position');
    }
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    // Accessor: resolved href
    public function getHrefAttribute(): ?string
    {
        // NOTE: extend with your real routes.
        if ($this->linkable instanceof \App\Models\Page) {
            return route('pages.show', $this->linkable->slug);
        }
        if ($this->linkable instanceof \App\Models\Post) {
            return route('posts.show', $this->linkable->slug);
        }
        return $this->url;
    }
}
