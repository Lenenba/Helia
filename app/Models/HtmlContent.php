<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HtmlContent extends Model
{
    use HasFactory;
    protected $fillable = ['content'];
    public function block(): MorphOne
    {
        return $this->morphOne(Block::class, 'blockable');
    }
}
