<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    /** @use HasFactory<\Database\Factories\TagFactory> */
    use HasFactory;

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Relationship: many-to-many with posts.
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tag')
            ->withTimestamps();
    }

    /**
     * Use slug for route model binding (e.g. /tags/{tag:slug}).
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Boot model events to ensure a unique slug is set automatically.
     */
    protected static function booted(): void
    {
        // On create: generate slug if not provided
        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = static::makeUniqueSlug($tag->name);
            } else {
                $tag->slug = static::makeUniqueSlug($tag->slug, isSlugAlready: true);
            }
        });

        // On update: if name changes and slug is empty or you want to keep slugs in sync
        // Uncomment the block below if you want slug to follow name updates
        /*
        static::updating(function (Tag $tag) {
            if ($tag->isDirty('name') && (empty($tag->slug) || $tag->wasChanged('name'))) {
                $tag->slug = static::makeUniqueSlug($tag->name);
            }
        });
        */
    }

    /**
     * Create a unique slug from a base string.
     *
     * @param  string  $value       The base string (name or slug candidate)
     * @param  bool    $isSlugAlready If true, $value is already a slug; otherwise we slugify it.
     */
    protected static function makeUniqueSlug(string $value, bool $isSlugAlready = false): string
    {
        $base = $isSlugAlready ? Str::slug($value) : Str::slug($value);
        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * Minimal search scope by name.
     */
    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where('name', 'like', '%' . $term . '%');
    }
}
