<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Block extends Model
{
    /** @use HasFactory<\Database\Factories\BlockFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'title',
        'content',
        'settings',
        'status',
        'media_id',
    ];
    // Attributs ajoutés à la réponse de l'API ou des données renvoyées
    protected $appends = ['created_at_human', 'updated_at_human'];

    protected $casts = [
        'is_published' => 'boolean',
        'settings'     => 'array',
    ];

    /** Polymorphic target (HtmlContent, Post, Media, ...) */
    public function blockable(): MorphTo
    {
        return $this->morphTo();
    }
    /**
     * Format human-readable for the 'created_at' attribute.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function createdAtHuman(): Attribute
    {
        return Attribute::get(fn() => optional($this->created_at)->diffForHumans());
    }

    /**
     * Format human-readable for the 'updated_at' attribute.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function updatedAtHuman(): Attribute
    {
        return Attribute::get(fn() => optional($this->updated_at)->diffForHumans());
    }

    /**
     * Get the sections associated with the block.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function sections()
    {
        return $this->belongsToMany(Section::class, 'block_section')
            ->withTimestamps()
            ->withPivot('order')
            ->orderBy('block_section.order');
    }

    /**
     * Get the media associated with the block.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * Scope to filter published blocks.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to filter blocks by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get the title for the block.
     * If the title is not set, fall back to a default title based on the ID.
     *
     * @param string|null $value
     * @return string
     */
    public function getTitleAttribute($value)
    {
        return $value ?: 'Block ' . $this->id;
    }

    /**
     * Get the custom settings for the block.
     * Return an empty array if no settings exist.
     *
     * @param mixed $value
     * @return array
     */
    public function getSettingsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
