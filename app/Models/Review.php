<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Review extends Model
{
    protected $guarded = [];
    /**
     * Define the inverse one-to-many relationship with the task model.
     * Indicates that this model belongs to a specific task.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Define the one-to-many relationship with the SubCategory model.
     * Indicates that this model can have multiple subcategories.
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
