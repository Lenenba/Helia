<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    /** @use HasFactory<\Database\Factories\SectionFactory> */
    use HasFactory;

    protected $fillable = [
        'page_id',
        'title',
        'slug',
        'is_published',
        'order',
        'type',
        'color',
        'settings',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
        'settings' => 'array', // Pour stocker des paramètres flexibles sous forme de tableau
    ];

    /**
     * Get the page that owns the section.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Get the blocks associated with the section.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function blocks()
    {
        return $this->belongsToMany(Block::class)->withPivot('order')->withTimestamps();
    }

    /**
     * Get the route key name.
     * This allows using the slug instead of the ID for route model binding.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get the URL for the section.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return route('sections.show', $this->slug);
    }

    /**
     * Scope to filter published sections.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to order sections by the `order` column.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope to filter sections by page.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $pageId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPage($query, $pageId)
    {
        return $query->where('page_id', $pageId);
    }

    /**
     * Get the title for the section.
     * Default to 'Section' with the order number if title is not set.
     *
     * @param string|null $value
     * @return string
     */
    public function getTitleAttribute($value)
    {
        return $value ?: 'Section ' . $this->order;
    }
}
