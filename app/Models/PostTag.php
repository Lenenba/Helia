<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PostTag extends Model
{
    use HasFactory;
    protected $table = 'post_tag'; // nom de la table pivot
    protected $fillable = ['post_id', 'tag_id', 'created_at', 'updated_at']; // champs remplissables
    public $timestamps = true; // activer les timestamps

    /**
     * Relationship:  each pivot entry belongs to one post.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Relationship: each pivot entry belongs to one tag.
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
