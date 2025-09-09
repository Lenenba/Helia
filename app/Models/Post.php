<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_media_id',
        'image_position',
        'show_title',
        'cover_image',
        'type',
        'status',
        'is_published',
        'published_at',
        'visibility',
        'views',
        'meta',
        'tags',
        'author_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'meta' => 'array', // Pour stocker les métadonnées JSON
        'tags' => 'array', // Pour stocker les tags JSON
    ];

    protected $appends = [
        'created_at_human',
        'updated_at_human',
        'cover_url'
    ];

    // On se charge du slug ici
    protected static function booted()
    {
        static::creating(function ($post) {
            // Si aucun slug n'est fourni, créer un slug basé sur le titre
            if (!$post->slug) {
                $post->slug = Str::slug($post->title);
            }

            // Vérifier l'unicité du slug et le rendre unique si nécessaire
            $post->slug = self::generateUniqueSlug($post->slug);
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
        // Vérifier si un slug existe déjà dans la table 'tags'
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

    /** Many-to-Many: Tags */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag')
            ->withTimestamps();
    }

    /** Many-to-Many: Sections */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'post_section')
            ->withTimestamps();
    }

    /** Many-to-Many: Pages */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(Page::class, 'post_page')
            ->withTimestamps();
    }

    /** Many-to-Many: Blocks */
    public function blocks(): BelongsToMany
    {
        return $this->belongsToMany(Block::class, 'post_block')
            ->withTimestamps();
    }

    /**
     * Get the author of the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the parent post of the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Post::class, 'parent_id');
    }

    /**
     * Get the cover image associated with the post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function coverImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediaable');
    }

    /**
     * Get the URL of the cover image.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function coverUrl(): Attribute
    {
        return Attribute::get(function () {
            // 1) Si un cover_media_id est défini, on tente en priorité
            if ($this->cover_media_id) {
                $m = Media::find($this->cover_media_id);
                if ($m) {
                    // Spatie MediaLibrary -> Media::getUrl()
                    if (method_exists($m, 'getUrl')) {
                        return $m->getUrl();
                    }
                    // Media "maison" avec path/disk
                    if (isset($m->path)) {
                        return Storage::disk($m->disk ?? 'public')->url($m->path);
                    }
                    // Dernier recours: champ url direct
                    return $m->url ?? null;
                }
            }

            // 2) Sinon, on tente la relation morphOne coverImage()
            $m = $this->relationLoaded('coverImage') ? $this->coverImage : $this->coverImage()->first();
            if ($m) {
                if (method_exists($m, 'getUrl')) {
                    return $m->getUrl();
                }
                if (isset($m->path)) {
                    return Storage::disk($m->disk ?? 'public')->url($m->path);
                }
                return $m->url ?? null;
            }

            // 3) Fallback éventuel (colonne legacy)
            return $this->cover_image ?: null;
        });
    }
}
