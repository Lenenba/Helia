<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Menu extends Model
{
    /** @use HasFactory<\Database\Factories\MenuFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'settings'];
    protected $casts = ['settings' => 'array'];

    public function roots(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')->orderBy('position');
    }

    public function tree(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->with('childrenRecursive')
            ->orderBy('position');
    }
}
