<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Section extends Model
{
    /** @use HasFactory<\Database\Factories\SectionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'is_published',
        'type',
        'color',
        'slug',
        'settings',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
        'settings' => 'array', // Pour stocker des paramètres flexibles sous forme de tableau
    ];


    /** Many-to-Many: Pages using this Section (ordered per page via pivot) */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'page_section')
            ->withPivot(['order'])
            ->withTimestamps()
            ->orderBy('page_section.order');
    }

    /** Many-to-Many: Blocks inside this Section (ordered via pivot) */
    public function blocks(): BelongsToMany
    {
        return $this->belongsToMany(Block::class, 'block_section')
            ->withPivot(['order'])
            ->withTimestamps()
            ->orderBy('block_section.order');
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
