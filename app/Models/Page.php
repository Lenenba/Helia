<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = ['created_at_human', 'updated_at_human'];

    // On se charge du slug ici
    protected static function booted()
    {
        static::creating(function ($page) {
            // Si aucun slug n'est fourni, créer un slug basé sur le titre
            if (!$page->slug) {
                $page->slug = Str::slug($page->title);
            }

            // Vérifier l'unicité du slug et le rendre unique si nécessaire
            $page->slug = self::generateUniqueSlug($page->slug);
        });
    }

    /**
     * Générer un slug unique en ajoutant un suffixe si nécessaire.
     *
     * @param string $slug
     * @return string
     */
    public static function generateUniqueSlug($slug)
    {
        // Vérifier si un slug existe déjà dans la table 'pages'
        $originalSlug = $slug;
        $counter = 1;

        // Tant qu'un slug avec le même nom existe, ajouter un suffixe
        while (self::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
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
     * Get the author of the page.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the parent page (if any).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    /**
     * Get the sections associated with the page.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    /**
     * Get the blocks associated with the page via sections.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function blocks()
    {
        return $this->hasManyThrough(Block::class, Section::class);
    }

    /**
     * Scope to filter published pages.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to filter pages by a specific status.
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
     * Get the URL for the page.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return route('pages.show', $this->slug);
    }

    /**
     * Get the title for the page.
     * If the title is not set, fall back to a default title based on the parent page.
     *
     * @param string|null $value
     * @return string
     */
    public function getTitleAttribute($value)
    {
        return $value ?: 'Page ' . $this->id;
    }

    /**
     * Get the custom settings for the page.
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
