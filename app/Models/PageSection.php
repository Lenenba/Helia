<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PageSection extends Model
{
    /** @use HasFactory<\Database\Factories\SectionFactory> */
    use HasFactory, SoftDeletes;
    protected $table = 'page_section';
    public $timestamps = true;

    protected $fillable = [
        'page_id',
        'section_id',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the page that owns the page-section relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Get the section that owns the page-section relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
