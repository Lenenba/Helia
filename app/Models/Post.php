<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
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
        'parent_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'meta' => 'array', // Pour stocker les métadonnées JSON
        'tags' => 'array', // Pour stocker les tags JSON
    ];
}
