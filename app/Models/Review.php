<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Review extends Model
{

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function images():MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
