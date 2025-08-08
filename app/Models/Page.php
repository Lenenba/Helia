<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Page extends Model
{
    /** @use HasFactory<\Database\Factories\PageFactory> */
    use HasFactory;

    protected $appends = ['created_at_human', 'updated_at_human'];

    protected function createdAtHuman(): Attribute
    {
        return Attribute::get(fn() => optional($this->created_at)->diffForHumans());
    }

    protected function updatedAtHuman(): Attribute
    {
        return Attribute::get(fn() => optional($this->updated_at)->diffForHumans());
    }
}
