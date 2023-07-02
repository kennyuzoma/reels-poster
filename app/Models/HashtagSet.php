<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Tags\HasTags;
use Spatie\Tags\Tag;

class HashtagSet extends Model
{
    use HasFactory, SoftDeletes, HasTags;

    public $appends = [
        'tag_list',
    ];

    public $fillable = [
        'account_id',
        'name',
        'tags'
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function getTagListAttribute()
    {
        return implode(' ', $this->tags->map(function (Tag $tag) {
            return '#'.$tag->name;
        })->values()->toArray()) ?? null;
    }


}
