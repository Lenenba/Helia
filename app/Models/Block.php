<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Block extends Model
{
    /** @use HasFactory<\Database\Factories\BlockFactory> */
    use HasFactory;

    // Attributs ajoutés à la réponse de l'API ou des données renvoyées
    protected $appends = ['created_at_human', 'updated_at_human'];

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
            ->withPivot('order')  // Get the order from the pivot table
            ->withTimestamps();
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
